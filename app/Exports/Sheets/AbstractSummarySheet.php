<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Facades\DB;

/**
 * Foglio "summary": aggregazione. Config ammessa: `group_by`, `metrics`.
 *
 * Le sottoclassi concrete dichiarano tabella, mappa dimensione→colonna DB e
 * mappa metrica→espressione SQL; validazione e rendering (GROUP BY in streaming,
 * con supporto alle dimensioni `payload.*`) sono qui, condivisi.
 */
abstract class AbstractSummarySheet extends AbstractDataSheet
{
    /**
     * Tabella di base dell'entità da aggregare.
     */
    abstract protected function table(): string;

    /**
     * Mappa dimensione logica di group_by → colonna reale sul DB.
     *
     * @return array<string,string>
     */
    abstract protected function groupByColumnMap(): array;

    /**
     * Mappa metrica → espressione SQL aggregata (es. 'count' => 'COUNT(*)').
     *
     * @return array<string,string>
     */
    abstract protected function metricMap(): array;

    /**
     * Colonna per il filtro temporale `date_from`/`date_to` (o null).
     */
    protected function timeColumn(): ?string
    {
        return null;
    }

    public function rows(): iterable
    {
        $groupBy = $this->config['group_by'] ?? [];
        $metrics = $this->config['metrics'] ?? [];
        $metricMap = $this->metricMap();

        // Intestazione: dimensioni + metriche (nomi logici richiesti).
        yield array_merge($groupBy, $metrics);

        $selectParts = [];
        $selectBindings = [];
        $dimAliases = [];

        $i = 0;
        foreach ($groupBy as $dim) {
            [$expr, $bindings] = $this->dimensionExpression($dim);
            $alias = 'g'.$i++;
            $dimAliases[] = $alias;
            $selectParts[] = $expr.' as '.$alias;
            $selectBindings = array_merge($selectBindings, $bindings);
        }

        $metricAliases = [];
        $j = 0;
        foreach ($metrics as $metric) {
            $alias = 'm'.$j++;
            $metricAliases[] = $alias;
            $selectParts[] = $metricMap[$metric].' as '.$alias;
        }

        $query = DB::table($this->table())
            ->where('version_id', $this->version->id)
            ->selectRaw(implode(', ', $selectParts), $selectBindings);

        $timeColumn = $this->timeColumn();
        if ($timeColumn !== null) {
            if ($this->dateFrom !== null) {
                $query->whereDate($timeColumn, '>=', $this->dateFrom);
            }
            if ($this->dateTo !== null) {
                $query->whereDate($timeColumn, '<=', $this->dateTo);
            }
        }

        if ($dimAliases !== []) {
            // Raggruppo per alias delle dimensioni: evita placeholder duplicati e
            // soddisfa only_full_group_by (l'alias riferisce l'espressione del SELECT).
            $query->groupByRaw(implode(', ', $dimAliases));
            $query->orderByRaw(implode(', ', $dimAliases)); // ordine stabile per dimensione
        }

        foreach ($query->cursor() as $record) {
            $row = [];
            foreach (array_merge($dimAliases, $metricAliases) as $alias) {
                $row[] = $record->{$alias} ?? null;
            }
            yield $row;
        }
    }

    /**
     * Espressione SQL (e bindings) per una dimensione di group_by:
     * colonna diretta oppure estrazione da payload.
     *
     * @return array{0:string,1:array<int,string>}
     */
    private function dimensionExpression(string $dim): array
    {
        $map = $this->groupByColumnMap();

        if (isset($map[$dim])) {
            return [$map[$dim], []];
        }

        // payload.* → estrazione JSON con path bound.
        return ['JSON_UNQUOTE(JSON_EXTRACT(payload, ?))', ['$.'.substr($dim, strlen('payload.'))]];
    }

    protected function allowedGroupBy(): array
    {
        return array_keys($this->groupByColumnMap());
    }

    protected function allowedMetrics(): array
    {
        return array_keys($this->metricMap());
    }

    public function validate(array $config): array
    {
        $errors = $this->unknownKeyErrors($config, ['name', 'group_by', 'metrics']);

        if (! isset($config['group_by'])) {
            $errors[] = "manca 'group_by' (obbligatorio per un foglio summary)";
        } elseif (! is_array($config['group_by']) || $config['group_by'] === []) {
            $errors[] = "'group_by' deve essere una lista non vuota";
        } else {
            foreach ($config['group_by'] as $dim) {
                if (! is_string($dim) || ! $this->isAllowedGroupBy($dim)) {
                    $errors[] = "dimensione group_by non ammessa: '".$this->stringify($dim)."'";
                }
            }
        }

        if (! isset($config['metrics'])) {
            $errors[] = "manca 'metrics' (obbligatorio per un foglio summary)";
        } elseif (! is_array($config['metrics']) || $config['metrics'] === []) {
            $errors[] = "'metrics' deve essere una lista non vuota";
        } else {
            foreach ($config['metrics'] as $metric) {
                if (! is_string($metric) || ! in_array($metric, $this->allowedMetrics(), true)) {
                    $errors[] = "metrica non ammessa: '".$this->stringify($metric)."'";
                }
            }
        }

        return $errors;
    }

    protected function isAllowedGroupBy(string $field): bool
    {
        return in_array($field, $this->allowedGroupBy(), true) || $this->isPayloadPath($field);
    }
}

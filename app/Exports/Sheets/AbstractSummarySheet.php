<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Facades\DB;

/**
 * Summary sheet: one row per group. Concrete sheets declare table(),
 * groupByColumnMap() and metricMap() (metric => SQL aggregate); validation and
 * the streamed GROUP BY rendering live here.
 */
abstract class AbstractSummarySheet extends AbstractDataSheet
{
    abstract protected function table(): string;

    /**
     * @return array<string,string> group_by dimension => DB column
     */
    abstract protected function groupByColumnMap(): array;

    /**
     * @return array<string,string> metric name => SQL aggregate
     */
    abstract protected function metricMap(): array;

    protected function timeColumn(): ?string
    {
        return null;
    }

    public function rows(): iterable
    {
        $groupBy = $this->config['group_by'] ?? [];
        $metrics = $this->config['metrics'] ?? [];
        $metricMap = $this->metricMap();

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
            // Group by the select aliases: avoids duplicated placeholders and
            // satisfies MySQL only_full_group_by.
            $query->groupByRaw(implode(', ', $dimAliases));
            $query->orderByRaw(implode(', ', $dimAliases));
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
     * @return array{0:string,1:array<int,string>} [SQL expression, bindings]
     */
    private function dimensionExpression(string $dim): array
    {
        $map = $this->groupByColumnMap();

        if (isset($map[$dim])) {
            return [$map[$dim], []];
        }

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

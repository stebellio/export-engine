<?php

namespace App\Exports\Sheets;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Foglio "detail": righe da una entità. Config ammessa: `columns`, `filters`, `sort`.
 *
 * Le sottoclassi concrete dichiarano solo la tabella, la mappa colonna→DB e la
 * colonna temporale; la validazione e il rendering (query in streaming, con
 * supporto opzionale ai campi `payload.*`) sono qui, condivisi.
 */
abstract class AbstractDetailSheet extends AbstractDataSheet
{
    /**
     * Tabella di base dell'entità.
     */
    abstract protected function table(): string;

    /**
     * Mappa colonna logica (lato client) → colonna reale sul DB.
     *
     * @return array<string,string>
     */
    abstract protected function columnMap(): array;

    /**
     * Colonna usata per il filtro temporale `date_from`/`date_to` (o null).
     */
    protected function timeColumn(): ?string
    {
        return null;
    }

    /**
     * Colonne logiche ammesse = chiavi della mappa (oltre agli eventuali payload.*).
     *
     * @return string[]
     */
    protected function allowedColumns(): array
    {
        return array_keys($this->columnMap());
    }

    public function rows(): iterable
    {
        $map = $this->columnMap();
        $columns = $this->config['columns'] ?? array_keys($map);

        // Intestazione.
        yield $columns;

        $query = DB::table($this->table())->where('version_id', $this->version->id);

        foreach (($this->config['filters'] ?? []) as $field => $value) {
            $this->applyFilter($query, (string) $field, $value);
        }

        $timeColumn = $this->timeColumn();
        if ($timeColumn !== null) {
            if ($this->dateFrom !== null) {
                $query->whereDate($timeColumn, '>=', $this->dateFrom);
            }
            if ($this->dateTo !== null) {
                $query->whereDate($timeColumn, '<=', $this->dateTo);
            }
        }

        foreach (($this->config['sort'] ?? []) as $entry) {
            [$col, $dir] = $this->parseSort($entry);
            $this->applySort($query, $col, $dir);
        }

        foreach ($query->cursor() as $record) {
            yield $this->mapRecord($record, $columns, $map);
        }
    }

    private function applyFilter(Builder $query, string $field, $value): void
    {
        $map = $this->columnMap();

        if (isset($map[$field])) {
            $query->where($map[$field], $value);
        } elseif ($this->isPayloadField($field)) {
            $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, ?)) = ?', [$this->jsonPath($field), $value]);
        }
    }

    private function applySort(Builder $query, string $col, string $dir): void
    {
        $map = $this->columnMap();

        if (isset($map[$col])) {
            $query->orderBy($map[$col], $dir);
        } elseif ($this->isPayloadField($col)) {
            // $dir è già normalizzato ad asc|desc, quindi sicuro da concatenare.
            $query->orderByRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, ?)) '.$dir, [$this->jsonPath($col)]);
        }
    }

    /**
     * Costruisce la riga di output nell'ordine delle colonne richieste,
     * risolvendo colonne dirette e campi payload.*.
     *
     * @param array<int,string> $columns
     * @param array<string,string> $map
     * @return array<int,mixed>
     */
    private function mapRecord(object $record, array $columns, array $map): array
    {
        $payload = null;
        $row = [];

        foreach ($columns as $logical) {
            if (isset($map[$logical])) {
                $row[] = $record->{$map[$logical]} ?? null;
            } elseif ($this->isPayloadField($logical)) {
                if ($payload === null) {
                    $payload = json_decode($record->payload ?? 'null', true);
                }
                $row[] = $this->digPayload(is_array($payload) ? $payload : [], substr($logical, strlen('payload.')));
            } else {
                $row[] = null;
            }
        }

        return $row;
    }

    private function isPayloadField(string $field): bool
    {
        return $this->supportsPayload() && strpos($field, 'payload.') === 0;
    }

    private function jsonPath(string $field): string
    {
        return '$.'.substr($field, strlen('payload.'));
    }

    /**
     * Estrae un valore annidato dal payload seguendo il path puntato.
     *
     * @param array<string,mixed> $payload
     * @return mixed
     */
    private function digPayload(array $payload, string $path)
    {
        $value = $payload;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return is_scalar($value) ? $value : null;
    }

    /**
     * @return array{0:string,1:string} [colonna, direzione]
     */
    private function parseSort(string $entry): array
    {
        $parts = explode(':', $entry);
        $col = $parts[0];
        $dir = strtolower($parts[1] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return [$col, $dir];
    }

    public function validate(array $config): array
    {
        $errors = $this->unknownKeyErrors($config, ['name', 'columns', 'filters', 'sort']);

        if (! isset($config['columns'])) {
            $errors[] = "manca 'columns' (obbligatorio per un foglio detail)";
        } elseif (! is_array($config['columns']) || $config['columns'] === []) {
            $errors[] = "'columns' deve essere una lista non vuota";
        } else {
            foreach ($config['columns'] as $col) {
                if (! is_string($col) || ! $this->isAllowedField($col)) {
                    $errors[] = "colonna non ammessa: '".$this->stringify($col)."'";
                }
            }
        }

        if (isset($config['filters'])) {
            if (! is_array($config['filters'])) {
                $errors[] = "'filters' deve essere un oggetto chiave/valore";
            } else {
                foreach ($config['filters'] as $field => $value) {
                    if (! is_string($field) || ! $this->isAllowedField($field)) {
                        $errors[] = "filtro non ammesso: '".$this->stringify($field)."'";
                    }
                    if (! is_scalar($value) && $value !== null) {
                        $errors[] = "il valore del filtro '".$this->stringify($field)."' deve essere scalare";
                    }
                }
            }
        }

        if (isset($config['sort'])) {
            if (! is_array($config['sort'])) {
                $errors[] = "'sort' deve essere una lista di stringhe 'colonna:asc|desc'";
            } else {
                foreach ($config['sort'] as $entry) {
                    $errors = array_merge($errors, $this->validateSortEntry($entry));
                }
            }
        }

        return $errors;
    }

    protected function isAllowedField(string $field): bool
    {
        return in_array($field, $this->allowedColumns(), true) || $this->isPayloadPath($field);
    }

    /**
     * @return string[]
     */
    private function validateSortEntry($entry): array
    {
        if (! is_string($entry)) {
            return ['voce di sort non valida: '.$this->stringify($entry)];
        }

        $parts = explode(':', $entry);
        if (count($parts) > 2) {
            return ["voce di sort malformata: '$entry'"];
        }

        $errors = [];
        $col = $parts[0];
        $dir = isset($parts[1]) ? strtolower($parts[1]) : 'asc';

        if (! $this->isAllowedField($col)) {
            $errors[] = "colonna di sort non ammessa: '$col'";
        }
        if (! in_array($dir, ['asc', 'desc'], true)) {
            $errors[] = "direzione di sort non valida in '$entry' (ammessi: asc, desc)";
        }

        return $errors;
    }
}

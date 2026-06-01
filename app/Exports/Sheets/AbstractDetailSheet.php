<?php

namespace App\Exports\Sheets;

/**
 * Foglio "detail": righe da una entità. Config ammessa: `columns`, `filters`, `sort`.
 * Le sottoclassi concrete dichiarano solo le colonne ammesse (e se c'è il payload).
 */
abstract class AbstractDetailSheet extends AbstractSheet
{
    /**
     * Colonne logiche ammesse (oltre agli eventuali `payload.*`).
     *
     * @return string[]
     */
    abstract protected function allowedColumns(): array;

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

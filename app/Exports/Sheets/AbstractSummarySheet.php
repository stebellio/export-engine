<?php

namespace App\Exports\Sheets;

/**
 * Foglio "summary": aggregazione. Config ammessa: `group_by`, `metrics`.
 * Le sottoclassi concrete dichiarano dimensioni e metriche ammesse.
 */
abstract class AbstractSummarySheet extends AbstractDataSheet
{
    /**
     * Dimensioni di group_by ammesse (oltre agli eventuali `payload.*`).
     *
     * @return string[]
     */
    abstract protected function allowedGroupBy(): array;

    /**
     * @return string[]
     */
    abstract protected function allowedMetrics(): array;

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

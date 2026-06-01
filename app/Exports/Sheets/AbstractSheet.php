<?php

namespace App\Exports\Sheets;

/**
 * Base comune a tutti i fogli: default e helper condivisi.
 *
 * I fogli restano "puri": espongono identità (`title`), contenuto (`rows`) e
 * validazione (`validate`), senza conoscere la libreria di scrittura — di quella
 * si occupa l'orchestratore.
 *
 * `validate()` ha default no-op (i metadata non validano nulla); i fogli dati
 * lo sovrascrivono nelle basi detail/summary.
 */
abstract class AbstractSheet implements SheetInterface
{
    public function validate(array $config): array
    {
        return [];
    }

    abstract public function title(): string;

    abstract public function rows(): iterable;

    /**
     * Se il foglio ammette colonne/dimensioni dentro il payload JSON (`payload.*`).
     */
    protected function supportsPayload(): bool
    {
        return false;
    }

    protected function isPayloadPath(string $field): bool
    {
        if (! $this->supportsPayload() || strpos($field, 'payload.') !== 0) {
            return false;
        }

        $path = substr($field, strlen('payload.'));

        return (bool) preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)*$/', $path);
    }

    /**
     * @return string[]
     */
    protected function unknownKeyErrors(array $config, array $allowedKeys): array
    {
        $errors = [];
        foreach (array_keys($config) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                $errors[] = "chiave non ammessa: '".$this->stringify($key)."'";
            }
        }

        return $errors;
    }

    protected function stringify($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return is_scalar($value) ? (string) $value : gettype($value);
    }
}

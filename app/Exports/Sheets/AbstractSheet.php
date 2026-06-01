<?php

namespace App\Exports\Sheets;

abstract class AbstractSheet implements SheetInterface
{
    public function validate(array $config): array
    {
        return [];
    }

    abstract public function title(): string;

    abstract public function rows(): iterable;

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

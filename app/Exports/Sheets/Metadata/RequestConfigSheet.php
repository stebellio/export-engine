<?php

namespace App\Exports\Sheets\Metadata;

use App\Exports\Sheets\AbstractSheet;

/**
 * Foglio metadata "Configurazione_Richiesta": echo appiattito della config
 * inviata dal client (utile per riprodurre/auditare l'export).
 */
class RequestConfigSheet extends AbstractSheet
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function title(): string
    {
        return 'Configurazione_Richiesta';
    }

    public function rows(): iterable
    {
        $rows = [['Parametro', 'Valore']];
        foreach ($this->flatten($this->config) as $param => $value) {
            $rows[] = [$param, $value];
        }

        return $rows;
    }

    /**
     * Appiattisce la config in coppie parametro/valore leggibili.
     *
     * @return array<string,string>
     */
    public function flatten(array $config): array
    {
        $sheets = is_array($config['sheets'] ?? null) ? $config['sheets'] : [];

        $names = [];
        $details = [];
        foreach ($sheets as $sheet) {
            if (! is_array($sheet) || ! isset($sheet['name']) || ! is_string($sheet['name'])) {
                continue;
            }

            $name = $sheet['name'];
            $names[] = $name;

            foreach (['columns', 'group_by', 'metrics', 'sort'] as $key) {
                if (isset($sheet[$key]) && is_array($sheet[$key])) {
                    $details["$name.$key"] = implode(', ', array_map('strval', $sheet[$key]));
                }
            }

            if (isset($sheet['filters']) && is_array($sheet['filters'])) {
                $pairs = [];
                foreach ($sheet['filters'] as $field => $value) {
                    $pairs[] = $field.'='.(is_scalar($value) ? (string) $value : json_encode($value));
                }
                $details["$name.filters"] = implode(', ', $pairs);
            }
        }

        $flat = [];
        $flat['format'] = isset($config['format']) ? (string) $config['format'] : 'xlsx';
        if (isset($config['date_from'])) {
            $flat['date_from'] = (string) $config['date_from'];
        }
        if (isset($config['date_to'])) {
            $flat['date_to'] = (string) $config['date_to'];
        }
        $flat['sheets'] = implode(', ', $names);

        return array_merge($flat, $details);
    }
}

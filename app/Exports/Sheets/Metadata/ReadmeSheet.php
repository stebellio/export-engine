<?php

namespace App\Exports\Sheets\Metadata;

use App\Exports\Sheets\AbstractSheet;
use App\Models\Export;
use Illuminate\Support\Carbon;

class ReadmeSheet extends AbstractSheet
{
    /** @var Export */
    private $export;

    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    public function title(): string
    {
        return 'README';
    }

    public function rows(): iterable
    {
        $config = (array) $this->export->config;

        return [
            ['Export', 'Statistiche versione'],
            ['Version ID', $this->export->version_id],
            ['Versione', optional($this->export->version)->name],
            ['Formato', $this->export->format],
            ['Generato il', Carbon::now()->format('Y-m-d H:i:s')],
            ['Periodo', $this->period($config)],
            ['Note', "File generato automaticamente dall'Export Engine."],
        ];
    }

    private function period(array $config): string
    {
        $from = $config['date_from'] ?? null;
        $to = $config['date_to'] ?? null;

        if (! $from && ! $to) {
            return 'tutto';
        }

        return ($from ?: '…').' - '.($to ?: '…');
    }
}

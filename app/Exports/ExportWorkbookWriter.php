<?php

namespace App\Exports;

use App\Exports\Sheets\Metadata\ReadmeSheet;
use App\Exports\Sheets\Metadata\RequestConfigSheet;
use App\Exports\Sheets\SheetInterface;
use App\Models\Export;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\WriterMultiSheetsAbstract;

/**
 * Orchestratore della generazione del file: assembla i fogli (prima i metadata,
 * poi quelli richiesti dalla config) e li scrive su disco in streaming.
 *
 * È l'unico punto che conosce la libreria di scrittura; i fogli restano puri.
 */
class ExportWorkbookWriter
{
    /** @var SheetRegistry */
    private $registry;

    public function __construct(SheetRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Genera il file dell'export e ritorna il path relativo al disco configurato.
     */
    public function write(Export $export): string
    {
        $directory = config('export.directory');
        $relativePath = $directory.'/export-'.$export->id.'.xlsx';

        $disk = Storage::disk(config('export.disk'));
        $disk->makeDirectory($directory);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($disk->path($relativePath));

        try {
            $usedNames = [];
            $first = true;
            foreach ($this->sheets($export) as $sheet) {
                $name = $this->uniqueName($sheet->title(), $usedNames);
                $this->writeSheet($writer, $sheet, $name, $first);
                $first = false;
            }
        } finally {
            $writer->close();
        }

        return $relativePath;
    }

    /**
     * Ordine dei fogli: metadata di testa, poi i fogli richiesti (ordine config).
     *
     * @return SheetInterface[]
     */
    private function sheets(Export $export): array
    {
        return array_merge($this->metadataSheets($export), $this->requestedSheets($export));
    }

    /**
     * @return SheetInterface[]
     */
    private function metadataSheets(Export $export): array
    {
        return [
            new ReadmeSheet($export),
            new RequestConfigSheet((array) $export->config),
        ];
    }

    /**
     * Fogli dati richiesti dal client, costruiti con il loro contesto
     * (versione + config del foglio + range temporale globale).
     *
     * @return SheetInterface[]
     */
    private function requestedSheets(Export $export): array
    {
        $config = (array) $export->config;
        $version = $export->version;
        $dateFrom = $config['date_from'] ?? null;
        $dateTo = $config['date_to'] ?? null;

        $sheets = [];
        foreach (($config['sheets'] ?? []) as $sheetConfig) {
            if (! is_array($sheetConfig) || ! isset($sheetConfig['name']) || ! is_string($sheetConfig['name'])) {
                continue;
            }

            $sheet = $this->registry->get($sheetConfig['name'], $version, $sheetConfig, $dateFrom, $dateTo);
            if ($sheet !== null) {
                $sheets[] = $sheet;
            }
        }

        return $sheets;
    }

    private function writeSheet(WriterMultiSheetsAbstract $writer, SheetInterface $sheet, string $name, bool $first): void
    {
        $current = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
        $current->setName($name);

        foreach ($sheet->rows() as $row) {
            $cells = array_map([$this, 'cell'], array_values((array) $row));
            $writer->addRow(WriterEntityFactory::createRowFromArray($cells));
        }
    }

    /**
     * Nome foglio valido e univoco nel workbook: caratteri ammessi, ≤31 char,
     * con suffisso incrementale in caso di collisione.
     *
     * @param array<string,bool> $usedNames
     */
    private function uniqueName(string $title, array &$usedNames): string
    {
        $base = $this->sanitizeTitle($title);
        $name = $base;
        $i = 2;
        while (isset($usedNames[$name])) {
            $suffix = '_'.$i++;
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
        }

        $usedNames[$name] = true;

        return $name;
    }

    /**
     * Nome foglio valido per Excel: niente caratteri proibiti, max 31 caratteri.
     */
    private function sanitizeTitle(string $title): string
    {
        $clean = trim(str_replace(['\\', '/', '?', '*', ':', '[', ']'], ' ', $title));

        return $clean === '' ? 'Sheet' : mb_substr($clean, 0, 31);
    }

    private function cell($value): string
    {
        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}

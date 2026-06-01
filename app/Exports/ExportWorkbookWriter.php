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
 * poi quelli richiesti) e li scrive su disco in streaming via OpenSpout.
 *
 * È l'unico punto che conosce la libreria di scrittura; i fogli restano puri.
 */
class ExportWorkbookWriter
{
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
            $first = true;
            foreach ($this->sheets($export) as $sheet) {
                $this->writeSheet($writer, $sheet, $first);
                $first = false;
            }
        } finally {
            $writer->close();
        }

        return $relativePath;
    }

    /**
     * Ordine dei fogli nel file: metadata di testa, poi i fogli richiesti.
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
     * Fogli dati richiesti dal client.
     *
     * TODO: dal prossimo step mappare $export->config['sheets'] alle classi via
     * SheetRegistry e renderizzarle. Per ora il file contiene i soli metadata.
     *
     * @return SheetInterface[]
     */
    private function requestedSheets(Export $export): array
    {
        return [];
    }

    private function writeSheet(WriterMultiSheetsAbstract $writer, SheetInterface $sheet, bool $first): void
    {
        $current = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
        $current->setName($this->sanitizeTitle($sheet->title()));

        foreach ($sheet->rows() as $row) {
            $cells = array_map([$this, 'cell'], array_values((array) $row));
            $writer->addRow(WriterEntityFactory::createRowFromArray($cells));
        }
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

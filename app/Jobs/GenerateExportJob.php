<?php

namespace App\Jobs;

use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use Throwable;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $timeout = 600;

    /** @var int */
    public $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle(): void
    {
        $export = Export::find($this->exportId);

        if ($export === null) {
            return;
        }

        $export->markProcessing();

        try {
            // Skeleton: simulate heavy work so "processing" is observable.
            $processingSeconds = (int) config('export.mock_processing_seconds');
            if ($processingSeconds > 0) {
                sleep($processingSeconds);
            }

            $path = $this->writeMockFile($export);

            $export->markCompleted($path);
        } catch (Throwable $e) {
            $export->markFailed($e->getMessage());

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $export = Export::find($this->exportId);

        if ($export !== null && $export->status !== Export::STATUS_FAILED) {
            $export->markFailed($e->getMessage());
        }
    }

    /**
     * Write a placeholder XLSX file. Real columns/data come later — for now it
     * only proves the async write-to-disk pipeline works end to end.
     *
     * @return string Path relative to the configured disk.
     */
    private function writeMockFile(Export $export): string
    {
        $directory = config('export.directory');
        $relativePath = $directory . '/export-' . $export->id . '.xlsx';

        $disk = Storage::disk(config('export.disk'));
        $disk->makeDirectory($directory);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($disk->path($relativePath));
        $writer->addRow(WriterEntityFactory::createRowFromArray([
            'mock export',
            'export_id',
            (string) $export->id,
        ]));
        $writer->close();

        return $relativePath;
    }
}

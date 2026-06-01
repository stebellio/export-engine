<?php

namespace App\Jobs;

use App\Exports\ExportWorkbookWriter;
use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    /**
     * @throws Throwable
     */
    public function handle(ExportWorkbookWriter $writer): void
    {
        $export = Export::find($this->exportId);

        if ($export === null) {
            return;
        }

        $export->markProcessing();

        try {
            $path = $writer->write($export);

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
}

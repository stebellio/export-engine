<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mock delays (skeleton only)
    |--------------------------------------------------------------------------
    | These delays exist so the asynchronous lifecycle is observable while the
    | real generation logic is not yet implemented. They will be removed once
    | the job does actual work.
    |
    | mock_delay_seconds: applied as a dispatch delay, keeps the export in the
    |                     "pending" state long enough to observe it.
    | mock_processing_seconds: slept inside the job, keeps it "processing".
    */
    'mock_delay_seconds' => (int) env('EXPORT_MOCK_DELAY_SECONDS', 10),
    'mock_processing_seconds' => (int) env('EXPORT_MOCK_PROCESSING_SECONDS', 5),

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    | Disk and directory (relative to the disk root) where generated files are
    | written.
    */
    'disk' => env('EXPORT_DISK', 'local'),
    'directory' => 'exports',
];

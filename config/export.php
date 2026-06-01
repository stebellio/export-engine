<?php

return [
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

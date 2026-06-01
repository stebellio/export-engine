<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['service' => 'export-engine', 'api' => '/api/v1']);
});

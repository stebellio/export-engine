<?php

use App\Http\Controllers\Api\V1\AnswerController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventStreamController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\RewardController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\VersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('versions', [VersionController::class, 'store']);
    Route::post('versions/{version}/players', [PlayerController::class, 'store']);
    Route::post('versions/{version}/events', [EventController::class, 'store']);
    Route::post('versions/{version}/events/stream', [EventStreamController::class, 'stream']);
    Route::post('versions/{version}/transactions', [TransactionController::class, 'store']);
    Route::post('versions/{version}/answers', [AnswerController::class, 'store']);
    Route::post('versions/{version}/rewards', [RewardController::class, 'store']);

    Route::post('versions/{version}/exports', [ExportController::class, 'store']);
    Route::get('exports/{export}', [ExportController::class, 'show']);
    Route::get('exports/{export}/download', [ExportController::class, 'download']);
});

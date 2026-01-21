<?php

use App\Domains\Setting\Trash\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->group(function () {
    Route::middleware(['auth:sanctum', 'permission.map'])->prefix('trash')->controller(TrashController::class)->group(function () {
        Route::get('/', 'list_trash');
        Route::get('/stats', 'stats_trash');
        Route::delete('/{modelType}/empty', 'empty_trash');
        Route::post('/{modelType}/{modelId}/restore', 'restore_trash');
        Route::delete('/{modelType}/{modelId}/force', 'force_delete');
    });
});

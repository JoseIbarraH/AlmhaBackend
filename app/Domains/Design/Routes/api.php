<?php

use App\Domains\Design\Controllers\DesignController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission.map'])->prefix('design')->controller(DesignController::class)->group(function () {
    Route::get('/', 'get_design');
    Route::post('/', 'create_item');
    Route::match(['post', 'put', 'patch'], '/{id}', 'update_item');
    Route::delete('/{id}', 'delete_item');

    Route::prefix('settings')->group(function () {
        Route::post('/state', 'update_state');
        Route::post('/toggle/{id}', 'toggle_item');
    });
});

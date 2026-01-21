<?php

use App\Domains\Setting\Setting\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Agrupamos por middleware y un solo prefijo 'settings' (en plural suele ser estándar)
Route::middleware(['auth:sanctum', 'permission.map'])
    ->prefix('settings')
    ->controller(SettingController::class)
    ->group(function () {
        Route::get('/', 'list_setting');
        Route::get('/{key}', 'get_setting');
        Route::get('/group/{group}', 'find_group');
        Route::put('/', 'update_settings');
    });

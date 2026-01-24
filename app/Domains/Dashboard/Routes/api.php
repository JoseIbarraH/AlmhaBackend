<?php

use App\Domains\Dashboard\Controllers\AnalyticsController;


Route::prefix('dashboard')->controller(AnalyticsController::class)->group(function () {
    Route::get('/', 'index');
});

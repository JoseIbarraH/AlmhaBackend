<?php

use App\Domains\Setting\Audit\Controllers\AuditController;


Route::prefix('settings')->group(function () {
    Route::middleware(['auth:sanctum', 'permission.map'])->prefix('audit')->controller(AuditController::class)->group(function () {
        Route::get('/', 'list_audit');
    });
});

<?php

use App\Domains\Procedure\Controllers\ProcedureController;
use App\Domains\Procedure\Controllers\ProcedureContentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission.map'])->prefix('procedure')->controller(ProcedureController::class)->group(function () {
    Route::get("/", "list_procedure");
    Route::post('/update_status/{id}', 'update_status');
    Route::get('/{id}', "get_procedure");
});

Route::middleware(['auth:sanctum', 'permission.map'])->prefix('procedure')->controller(ProcedureContentController::class)->group(function () {
    Route::post("/", "create_procedure");
    Route::patch("/{id}", "update_procedure");
    Route::delete("/{id}", "delete_procedure");
});


<?php

use App\Domains\Procedure\Controllers\ProcedureController;
use App\Domains\Procedure\Controllers\ProcedureContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('procedure')->controller(ProcedureController::class)->group(function () {
    Route::get("/", "list_procedure");
    Route::post("/", "create_procedure");
});
Route::prefix('procedure')->controller(ProcedureContentController::class)->group(function () {
    Route::patch("/{id}", "update_procedure");
});


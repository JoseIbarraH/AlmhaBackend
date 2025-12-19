<?php

use App\Domains\Procedure\Controllers\ProcedureController;
use Illuminate\Support\Facades\Route;

Route::prefix('procedure')->controller(ProcedureController::class)->group(function () {
    Route::get("/", "list_procedure");
    Route::post("/", "create_procedure");
});


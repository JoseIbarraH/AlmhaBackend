<?php


use App\Domains\Client\Controllers\ProcedureClientController;
use App\Domains\Client\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->controller(ClientController::class)->group(function () {
    Route::get('/maintenance', 'maintenance');
    Route::get('/navbar-data', 'navbarData');
});

Route::prefix('client')->controller(ProcedureClientController::class)->group(function () {
    Route::get('/procedure', 'list_procedure');
    Route::get('/procedure/{slug}', 'get_procedure');
});

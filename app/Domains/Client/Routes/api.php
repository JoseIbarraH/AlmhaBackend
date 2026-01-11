<?php


use App\Domains\Client\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->controller(ClientController::class)->group(function () {
    Route::get('/maintenance', 'maintenance');

    Route::get('/procedures', 'list_procedures');

});

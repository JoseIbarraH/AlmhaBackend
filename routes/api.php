<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->controller(ClientController::class)->group(function () {
    Route::get('/design', 'get_design_client');
    Route::get('/service/{slug}', 'get_service_client');
    Route::get('/blog', 'list_blog_client');
});

require __DIR__ . '/auth.php';

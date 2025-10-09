<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::middleware('web')->post('/login', [AuthenticatedSessionController::class, 'store']);
Route::middleware('web')->post('/logout', [AuthenticatedSessionController::class, 'destroy']);

require __DIR__.'/auth.php';

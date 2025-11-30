<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/unauthenticated', function () {
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('login');


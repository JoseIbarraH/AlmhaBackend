<?php

use App\Domains\Auth\Controllers\AuthenticatedSessionController;
use App\Domains\Auth\Controllers\NewPasswordController;
use App\Domains\Auth\Controllers\RefreshTokenController;
use Illuminate\Http\Request;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user()->load([
        'roles.permissions'
    ]);
});

Route::middleware(['web'])->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});

Route::post('/reset-password', [NewPasswordController::class, 'store']);
Route::post('refresh-token', [RefreshTokenController::class, 'refreshToken']);



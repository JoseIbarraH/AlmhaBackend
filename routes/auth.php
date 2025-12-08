<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\NewPasswordController;
use Illuminate\Http\Request;

/* Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
}); */

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user()->load([
        'roles.permissions'
    ]);
});

Route::middleware(['web'])->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});

/* Route::post('/register', [RegisteredUserController::class, 'store']); */
Route::post('/reset-password', [NewPasswordController::class, 'store']);
Route::post('refresh-token', [RefreshTokenController::class, 'refreshToken']);

/* Route::middleware(['auth'])->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register');
});
 */

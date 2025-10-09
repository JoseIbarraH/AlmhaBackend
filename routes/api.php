<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('web')->post('/login', [AuthenticatedSessionController::class, 'store']);
Route::middleware('web')->post('/logout', [AuthenticatedSessionController::class, 'destroy']);

Route::post('/register', [RegisteredUserController::class, 'store']);

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

Route::post('refresh-token', [RefreshTokenController::class, 'refreshToken']);

Route::middleware(['auth:sanctum', 'token.not.expired'])->group(function () {
    /* Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout'); */

    Route::get('/validate-token', function (Request $request) {
        return response()->json([
            'valid' => true,
            'user' => $request->user(),
        ]);
    });
});




Route::middleware('auth:sanctum')->prefix('design')->group(function () {
    Route::get('/designs', [DesignController::class, 'index']);
    Route::post('/background', [DesignController::class, 'backgroundStore']);
});

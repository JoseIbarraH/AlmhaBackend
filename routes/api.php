<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/* Route::middleware('auth')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
}); */

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('web')->post('/login', [AuthenticatedSessionController::class, 'store']);
Route::middleware('web')->post('/logout', [AuthenticatedSessionController::class, 'destroy']);

Route::post('/register', [RegisteredUserController::class, 'store']);

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

Route::post('refresh-token', [RefreshTokenController::class, 'refreshToken']);

/* Route::middleware(['auth:sanctum', 'token.not.expired'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/validate-token', function (Request $request) {
        return response()->json([
            'valid' => true,
            'user' => $request->user(),
        ]);
    });
}); */

Route::prefix('service')->group(function () {
    // Listar todos los servicios
    Route::get('/', [ServiceController::class, 'list_services']);

    // Ver detalles de un servicio específico
    Route::get('/{id}', [ServiceController::class, 'get_service']);

    // Crear un nuevo servicio
    Route::post('/', [ServiceController::class, 'create_service']);

    // Actualizar un servicio existente
    Route::match(['post', 'put', 'patch'], '/{id}', [ServiceController::class, 'update_service']);

    // Eliminar un servicio
    Route::delete('/{id}', [ServiceController::class, 'delete_service']);

    // Actualizar solo el estado del servicio
    Route::post('/update_status/{id}', [ServiceController::class, 'update_status']);
});

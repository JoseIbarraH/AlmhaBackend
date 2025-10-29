<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TeamMemberController;
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
Route::middleware(['web','auth:sanctum'])->post('/logout', [AuthenticatedSessionController::class, 'destroy']);

Route::post('/register', [RegisteredUserController::class, 'store']);

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

Route::post('refresh-token', [RefreshTokenController::class, 'refreshToken']);


Route::prefix('service')->group(function () {
    Route::get('/', [ServiceController::class, 'list_services']);
    Route::get('/{id}', [ServiceController::class, 'get_service']);
    Route::post('/', [ServiceController::class, 'create_service']);
    Route::match(['post', 'put', 'patch'], '/{id}', [ServiceController::class, 'update_service']);
    Route::delete('/{id}', [ServiceController::class, 'delete_service']);
    Route::post('/update_status/{id}', [ServiceController::class, 'update_status']);
});

Route::prefix('blog')->group(function () {
    Route::get('/', [BlogController::class, 'list_blogs']);
    Route::get('/{id}', [BlogController::class, 'get_blog']);
    Route::post('/', [BlogController::class, 'create_blog']);
    Route::match(['post', 'put', 'patch'], '/{id}', [BlogController::class, 'update_blog']);
    Route::delete('/{id}', [BlogController::class, 'delete_blog']);
});

Route::prefix('team_member')->group(function () {
    Route::get('/', [TeamMemberController::class, 'list_teamMember']);
    Route::get('/{id}', [TeamMemberController::class, 'get_teamMember']);
    Route::post('/', [TeamMemberController::class, 'create_teamMember']);
    Route::match(['post', 'put', 'patch'],'/{id}', [TeamMemberController::class, 'update_teamMember']);
    Route::delete('/{id}', [TeamMemberController::class, 'delete_teamMember']);
    Route::post('/update_status/{id}', [TeamMemberController::class, 'update_status']);
});

Route::prefix('design')->group(function () {

    Route::get('/', [DesignController::class, 'get_design']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::match(['post', 'patch'], '/carouselImage', [DesignController::class, 'update_carouselImage']);
        Route::match(['post', 'patch'], '/backgrounds', [DesignController::class, 'update_backgrounds']);
        Route::match(['post', 'patch'], '/carouselNavbar', [DesignController::class, 'update_carouselNavbar']);
        Route::match(['post', 'patch'], '/carouselTool', [DesignController::class, 'update_carouselTool']);
    });
});

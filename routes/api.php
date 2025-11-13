<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('service')->group(function () {
    Route::get('/', [ServiceController::class, 'list_services']);
    Route::get('/client/{id}', [ServiceController::class, 'get_service_client']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{id}', [ServiceController::class, 'get_service']);
        Route::post('/', [ServiceController::class, 'create_service']);
        Route::match(['post', 'put', 'patch'], '/{id}', [ServiceController::class, 'update_service']);
        Route::delete('/{id}', [ServiceController::class, 'delete_service']);
        Route::post('/update_status/{id}', [ServiceController::class, 'update_status']);
    });
});

Route::prefix('blog')->group(function () {
    Route::get('/', [BlogController::class, 'list_blogs']);
    Route::get('/client/{id}', [BlogController::class, 'get_blog_client']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{id}', [BlogController::class, 'get_blog']);
        Route::post('/', [BlogController::class, 'create_blog']);
        Route::match(['post', 'put', 'patch'], '/{id}', [BlogController::class, 'update_blog']);
        Route::delete('/{id}', [BlogController::class, 'delete_blog']);
        Route::post('/update_status/{id}', [BlogController::class, 'update_status']);
        Route::post('/upload_image/{id}', [BlogController::class, 'upload_image']);
        Route::delete('/delete_image/{id}', [BlogController::class, 'delete_image']);
    });
});

Route::prefix('team_member')->group(function () {
    Route::get('/', [TeamMemberController::class, 'list_teamMember']);
    Route::get('/client/{id}', [TeamMemberController::class, 'get_teamMember_client']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{id}', [TeamMemberController::class, 'get_teamMember']);
        Route::post('/', [TeamMemberController::class, 'create_teamMember']);
        Route::match(['post', 'put', 'patch'], '/{id}', [TeamMemberController::class, 'update_teamMember']);
        Route::delete('/{id}', [TeamMemberController::class, 'delete_teamMember']);
        Route::post('/update_status/{id}', [TeamMemberController::class, 'update_status']);
    });
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

Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::post('/info', [ProfileController::class, 'update']);
    Route::post('/password', [PasswordController::class, 'update']);
    Route::delete('/delete', [ProfileController::class, 'destroy']);
});

require __DIR__ . '/auth.php';

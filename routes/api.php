<?php

use App\Http\Controllers\Setting\ProfileController;
use App\Http\Controllers\Setting\RoleController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('service')->controller(ServiceController::class)->group(function () {
    Route::prefix('client')->group(function () {
        Route::get('/', 'list_service_client');
        Route::get('/{id}', 'get_service_client');
    });

    Route::middleware(['auth:sanctum', 'permission.map'])->group(function () {
        Route::get('/',  'list_service');
        Route::get('/{id}',  'get_service');
        Route::post('/',  'create_service');
        Route::match(['post', 'put', 'patch'], '/{id}',  'update_service');
        Route::delete('/{id}',  'delete_service');
        Route::post('/update_status/{id}',  'update_status');
    });
});

Route::prefix('blog')->controller(BlogController::class)->group(function () {
    // Rutas públicas
    Route::get('/client', 'list_blog_client');
    Route::get('/client/{id}', 'get_blog_client');

    // Rutas protegidas
    Route::middleware(['auth:sanctum', 'permission.map'])->group(function () {
        Route::get('/', 'list_blog');
        Route::get('/{id}', 'get_blog');
        Route::post('/', 'create_blog');
        Route::match(['post', 'put', 'patch'], '/{id}', 'update_blog');
        Route::delete('/{id}', 'delete_blog');
        Route::post('/update_status/{id}', 'update_status');
        Route::post('/upload_image/{id}', 'upload_image');
        Route::delete('/delete_image/{id}', 'delete_image');
    });
});


Route::prefix('team_member')->controller(TeamMemberController::class)->group(function () {
    // Públicas
    Route::get('/client', 'list_teamMember_client');
    Route::get('/client/{id}', 'get_teamMember_client');

    // Protegidas
    Route::middleware(['auth:sanctum', 'permission.map'])->group(function () {
        Route::get('/', 'list_teamMember');
        Route::get('/{id}', 'get_teamMember');
        Route::post('/', 'create_teamMember');
        Route::match(['post', 'put', 'patch'], '/{id}', 'update_teamMember');
        Route::delete('/{id}', 'delete_teamMember');
        Route::post('/update_status/{id}', 'update_status');
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

Route::middleware('auth:sanctum')->prefix('setting')->group(function () {
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::post('/info', 'update_account');
        Route::post('/password', 'change_password');
        Route::delete('/delete', 'destroy_account');
    });

    Route::prefix('role')->controller(RoleController::class)->group( function () {
        Route::get('/', 'list_role');
        Route::get('/permits', 'list_permission');
        Route::post('/', 'create_role');
        Route::patch('/{id}', 'update_role');
    });
});

require __DIR__ . '/auth.php';

<?php

use App\Http\Controllers\Setting\AuditController;
use App\Http\Controllers\Setting\ProfileController;
use App\Http\Controllers\Setting\RoleController;
use App\Http\Controllers\Setting\TrashController;
use App\Http\Controllers\Setting\UserController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ClientController;
/* use App\Http\Controllers\BlogController; */
use App\Domains\Blog\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('service')->controller(ServiceController::class)->group(function () {
    Route::prefix('client')->group(function () {
        Route::get('/', 'list_service_client');
        Route::get('/{id}', 'get_service_client');
    });

    Route::middleware(['auth:sanctum', 'permission.map'])->group(function () {
        Route::get('/', 'list_service');
        Route::get('/{id}', 'get_service');
        Route::post('/', 'create_service');
        Route::match(['post', 'put', 'patch'], '/{id}', 'update_service');
        Route::delete('/{id}', 'delete_service');
        Route::post('/update_status/{id}', 'update_status');
    });
});

Route::prefix('blog')->controller(BlogController::class)->group(function () {
    // Rutas públicas
    Route::get('/client/{id}', 'get_blog_client');
    Route::get('/client', 'list_blog_client');

    // Rutas protegidas
    Route::middleware(['auth:sanctum', 'permission.map'])->group(function () {
        Route::get('/', 'list_blog');
        Route::get('/categories', 'get_categories');
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

Route::middleware(['auth:sanctum', 'permission.map'])->prefix('design')->controller(DesignController::class)->group(function () {
    Route::get('/', 'get_design');
    Route::post('/', 'create_item');
    Route::match(['post', 'put', 'patch'], '/{id}', 'update_item');
    Route::delete('/{id}', 'delete_item');

    Route::prefix('settings')->group(function () {
        Route::post('/state', 'update_state');
    });

});

Route::prefix('setting')->group(function () {
    Route::middleware(['auth:sanctum', 'permission.map'])->prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::post('/info', 'update_account');
        Route::post('/password', 'change_password');
        Route::delete('/delete', 'destroy_account');
    });

    Route::middleware(['auth:sanctum', 'permission.map'])->prefix('role')->controller(RoleController::class)->group(function () {
        Route::get('/', 'list_role');
        Route::get('/permits', 'list_permission');
        Route::post('/', 'create_role');
        Route::match(['post', 'put', 'patch'], '/{id}', 'update_role');
        Route::post('/update_status/{id}', 'update_status');
        Route::delete('/{id}', 'delete_role');
    });

    Route::middleware(['auth:sanctum', 'permission.map'])->prefix('user')->controller(UserController::class)->group(function () {
        Route::get('/', 'list_user');
        Route::post('/', 'create_user');
        Route::match(['post', 'put', 'patch'], '/{id}', 'update_user');
        Route::post('/update_status/{id}', 'update_status');
        Route::get('/roles', 'list_role');
        Route::delete('/{id}', 'delete_user');
    });

    Route::middleware(['auth:sanctum', 'permission.map'])->prefix('audit')->controller(AuditController::class)->group(function () {
        Route::get('/', 'list_audit');
    });

    Route::prefix('trash')->controller(TrashController::class)->group(function () {
        Route::get('/', 'list_trash');
        Route::get('/stats', 'stats_trash');
        Route::delete('/{modelType}/empty', 'empty_trash');
        Route::post('/{modelType}/{modelId}/restore', 'restore_trash');
        Route::delete('/{modelType}/{modelId}/force', 'force_delete');
    });
});

Route::prefix('client')->controller(ClientController::class)->group(function () {
    Route::get('/design', 'get_design_client');
    Route::get('/service/{slug}', 'get_service_client');
    Route::get('/blog', 'list_blog_client');
});

require __DIR__ . '/auth.php';

<?php

use App\Http\Controllers\Setting\AuditController;
use App\Http\Controllers\Setting\ProfileController;
use App\Http\Controllers\Setting\RoleController;
use App\Http\Controllers\Setting\TrashController;
use App\Http\Controllers\Setting\UserController;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;




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

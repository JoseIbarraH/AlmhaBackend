<?php

use App\Domains\Setting\User\Controllers\ProfileController;
use App\Domains\Setting\User\Controllers\RoleController;
use App\Domains\Setting\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('setting')->group(function () {

    Route::middleware(['auth:sanctum', 'permission.map'])->prefix('user')->controller(UserController::class)->group(function () {
        Route::get('/', 'list_user');
        Route::post('/', 'create_user');
        Route::match(['post', 'put', 'patch'], '/{id}', 'update_user');
        Route::post('/update_status/{id}', 'update_status');
        Route::get('/roles', 'list_role');
        Route::delete('/{id}', 'delete_user');
    });

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
});

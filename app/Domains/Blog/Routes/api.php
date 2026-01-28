<?php

use App\Domains\Blog\Controllers\BlogController;
use App\Domains\Blog\Controllers\BlogCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission.map'])->prefix('blog')->controller(BlogController::class)->group(function () {
    Route::get('/', 'list_blog');
    Route::get('/{id}', 'get_blog');
    Route::post('/', 'create_blog');
    Route::match(['post', 'put', 'patch'], '/{id}', 'update_blog');
    Route::delete('/{id}', 'delete_blog');
    Route::post('/update_status/{id}', 'update_status');
    Route::post('/publish/{id}', 'publish_blog');
    Route::post('/upload_image/{id}', 'upload_image');
    Route::delete('/delete_image/{id}', 'delete_image');
});

Route::middleware(['auth:sanctum', 'permission.map'])->prefix('blog-category')->controller(BlogCategoryController::class)->group(function () {
    Route::get('/', 'list_categories');
    Route::post('/', 'create_category');
    Route::put('/{id}', 'update_category');
    Route::delete('/{id}', 'delete_category');
});

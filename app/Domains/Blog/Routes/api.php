<?php

use App\Domains\Blog\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission.map'])->prefix('blog')->controller(BlogController::class)->group(function () {
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

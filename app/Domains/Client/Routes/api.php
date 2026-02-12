<?php


use App\Domains\Client\Controllers\HomeController;
use App\Domains\Client\Controllers\ProcedureClientController;
use App\Domains\Client\Controllers\ContactPageController;
use App\Domains\Client\Controllers\BlogClientController;
use App\Domains\Client\Controllers\ClientController;
use App\Domains\Client\Controllers\TeamMemberClientController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->controller(ClientController::class)->group(function () {
    Route::get('/maintenance', 'maintenance');
    Route::get('/navbar-data', 'navbarData');
});

Route::prefix('client')->controller(ContactPageController::class)->group(function () {
    Route::get('/contact-data', 'index');
});

Route::prefix('client')->controller(ProcedureClientController::class)->group(function () {
    Route::get('/procedure', 'list_procedure');
    Route::get('/procedure/{slug}', 'get_procedure');
});

Route::prefix('client')->controller(BlogClientController::class)->group(function () {
    Route::get('/blog', 'list_blog');
    Route::get('/blog/{slug}', 'get_blog');
    Route::post('/subscribe', 'subscribe');
});

Route::prefix('client')->controller(TeamMemberClientController::class)->group(function () {
    Route::get('/members', 'list_members');
    Route::get('/members/{slug}', 'get_member');
});

Route::prefix('client')->controller(HomeController::class)->group(function () {
    Route::get('/home', 'getHomeData');
});

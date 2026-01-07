<?php

use Illuminate\Support\Facades\Route;
use App\Domains\TeamMember\Controllers\TeamMemberController;
use App\Domains\TeamMember\Controllers\TeamMemberContentController;


Route::prefix('team_member')
    ->controller(TeamMemberController::class)->group(function () {
        Route::get('/', 'list_teamMember');
        Route::get('/{id}', 'get_teamMember');
    });

Route::prefix('team_member')
    ->controller(TeamMemberContentController::class)->group(function () {
        Route::post("/", 'create_teamMember');
        Route::patch("/{id}", 'update_teamMember');
        Route::delete("/{id}", 'delete_teamMember');
        Route::post("/update_status/{id}", 'update_status');
    });

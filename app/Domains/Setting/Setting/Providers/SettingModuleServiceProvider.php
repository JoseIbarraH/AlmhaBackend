<?php

namespace App\Domains\Setting\Setting\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SettingModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        Route::prefix('api')->middleware('api')->group(__DIR__ . '/../Routes/api.php');
    }
}

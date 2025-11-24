<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GoogleTranslateService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleTranslateService::class, function ($app) {
            return new GoogleTranslateService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /* ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        }); */
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

use Illuminate\Support\Facades\Schema;
use App\Services\FolderService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //yên
        $this->app->singleton(FolderService::class, function ($app) {
            return new FolderService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);
    }
}

<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Workbench\App\Nova\Schema;
use Workbench\App\Nova\User;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Route::get('/login', fn () => redirect('/nova/login'))->name('login');
    }

    public function tools(): array
    {
        return [];
    }

    protected function resources(): void
    {
        Nova::resources([
            User::class,
            Schema::class,
        ]);
    }

    protected function gate(): void
    {
        Gate::define('viewNova', fn ($user) => true);
    }

    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    protected function dashboards(): array
    {
        return [
            new Main,
        ];
    }
}

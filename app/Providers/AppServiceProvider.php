<?php

namespace App\Providers;

use App\Models\Company;
use App\Policies\CompanyPolicy;
use App\Repositories\CompanyRepository;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);

        Vite::prefetch(concurrency: 3);
    }
}

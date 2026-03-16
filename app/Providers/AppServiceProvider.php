<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SidebarTipPage;
use App\Policies\CompanyPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\SidebarTipPagePolicy;
use App\Repositories\CompanyRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\SidebarTipPageRepository;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\SidebarTipPageRepositoryInterface;
use App\Services\SidebarTipPageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(SidebarTipPageRepositoryInterface::class, SidebarTipPageRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!\defined('APP_ACTIVE'))   \define('APP_ACTIVE', 1);
        if (!\defined('APP_INACTIVE')) \define('APP_INACTIVE', 0);

        if (!\defined('APP_TRUE'))     \define('APP_TRUE', true);
        if (!\defined('APP_FALSE'))    \define('APP_FALSE', false);

        Inertia::share([
            'errors' => function () {
                return Session::get('errors')
                    ? Session::get('errors')->getBag('default')->getMessages()
                    : (object) [];
            },
            'available_locales' => fn () => config('app.available_locales'),
            'supported_locales' => fn () => config('app.supported_locales'),
            'locale' => fn () => app()->getLocale(),
            'preferences' => fn () => [
                'locale' => app()->getLocale(),
                'timezone' => Session::has('timezone') ? Session::get('timezone') : config('app.timezone', 'UTC'),
                'theme' => Session::has('theme') ? Session::get('theme') : 'system',
            ],
            'sidebar_tips' => fn () => request()->user()
                ? app(SidebarTipPageService::class)->resolveForRoute(request()->route()?->getName())
                : [
                    'visible' => false,
                    'rotationIntervalMs' => 60 * 1000,
                    'tips' => [],
                ],
        ]);

        Inertia::share('flash', function () {
            return fn () => [ 'message' => Session::get('message') ];
        });

        Inertia::share('csrf_token', function () {
            return csrf_token();
        });

        Builder::macro('whereLike', function ($attributes, string $search) {
            /** @phpstan-var \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $this */
            /** @phpstan-param array<int,string>|string $attributes */
            /** @phpstan-return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> */
            $attributes = Arr::wrap($attributes);

            $search = trim($search);
            if ($search === '') {
                return $this;
            }

            $grammar = $this->getQuery()->getGrammar();
            $like    = $grammar instanceof PostgresGrammar ? 'ilike' : 'like';

            $terms = preg_split('/\s+/', $search) ?: [$search];

            return $this->where(function ($q) use ($attributes, $terms, $like) {
                foreach ($terms as $term) {
                    $q->where(function ($qq) use ($attributes, $term, $like) {
                        foreach ($attributes as $attr) {
                            if (str_contains($attr, '.')) {
                                [$relation, $relAttr] = explode('.', $attr, 2);
                                $qq->orWhereHas($relation, function ($rq) use ($relAttr, $term, $like) {
                                    $rq->where($relAttr, $like, "%{$term}%");
                                });
                            } else {
                                $qq->orWhere($attr, $like, "%{$term}%");
                            }
                        }
                    });
                }
            });
        });

        Builder::macro('orWhereLike', function ($attributes, string $search) {
            /** @phpstan-var \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $this */
            /** @phpstan-param array<int,string>|string $attributes */
            /** @phpstan-return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> */
            $attributes = Arr::wrap($attributes);
            $search     = trim($search);
            if ($search === '') {
                return $this;
            }

            $grammar = $this->getQuery()->getGrammar();
            $like    = $grammar instanceof PostgresGrammar ? 'ilike' : 'like';

            $terms = preg_split('/\s+/', $search) ?: [$search];

            return $this->orWhere(function ($q) use ($attributes, $terms, $like) {
                foreach ($terms as $term) {
                    $q->where(function ($qq) use ($attributes, $term, $like) {
                        foreach ($attributes as $attr) {
                            if (str_contains($attr, '.')) {
                                [$rel, $relAttr] = explode('.', $attr, 2);
                                $qq->orWhereHas($rel, function ($rq) use ($relAttr, $term, $like) {
                                    $rq->where($relAttr, $like, "%{$term}%");
                                });
                            } else {
                                $qq->orWhere($attr, $like, "%{$term}%");
                            }
                        }
                    });
                }
            });
        });

        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(SidebarTipPage::class, SidebarTipPagePolicy::class);

        Vite::prefetch(concurrency: 3);
    }
}

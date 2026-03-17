<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }
    
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_companies', false);

        $page = Paginator::resolveCurrentPage('page');
        $appendQuery = $this->buildAppendQuery($filters, $perPage, $page);

        $queryCallback = function () use ($filters, $perPage, $page, $appendQuery): LengthAwarePaginator {
            $paginator = $this->buildIndexQuery($filters)
                ->orderBy('name')
                ->paginate($perPage, ['*'], 'page', $page);

            $paginator->appends($appendQuery);

            return $paginator;
        };

        if (! $needCache) {
            return $queryCallback();
        }

        return $this->cacheService->remember(
            $this->companiesCacheTag(),
            $this->buildPaginateCacheKey($filters, $perPage, $page),
            $queryCallback,
            $this->paginateCacheTtlInSeconds(),
        );
    }

    public function create(array $attributes): Company
    {
        $company = Company::query()->create($attributes);

        $this->flushListCache();

        return $company;
    }

    public function update(Company $company, array $attributes): Company
    {
        $company->update($attributes);

        $this->flushListCache();

        return $company->refresh();
    }

    public function toggleActiveStatus(Company $company): Company
    {
        $company->update([
            'is_active' => ! $company->is_active,
        ]);

        $this->flushListCache();

        return $company->refresh();
    }

    public function delete(Company $company): bool
    {
        $deleted = (bool) $company->delete();

        if ($deleted) {
            $this->flushListCache();
        }

        return $deleted;
    }

    public function bulkDeleteByIds(array $ids): int
    {
        $deletedCount = Company::query()
            ->whereIn('id', $ids)
            ->delete();

        if ($deletedCount > 0) {
            $this->flushListCache();
        }

        return $deletedCount;
    }

    public function optionsForSelect(): array
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company) => [
                'value' => $company->id,
                'label' => $company->name,
            ])
            ->values()
            ->all();
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;
        $name = $filters['name'] ?? null;
        $email = $filters['email'] ?? null;
        $phone = $filters['phone'] ?? null;
        $isActive = $filters['is_active'] ?? null;

        return Company::query()
            ->withCount('employees')
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($companyQuery) use ($searchTerm) {
                    $companyQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
            })
            ->when($name, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->when($email, fn ($query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($phone, fn ($query, $value) => $query->where('phone', 'like', "%{$value}%"))
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('is_active', $isActive);
            });
    }

    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'name' => $filters['name'] ?? null,
            'email' => $filters['email'] ?? null,
            'phone' => $filters['phone'] ?? null,
            'is_active' => $filters['is_active'] ?? null,
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildPaginateCacheKey(array $filters, int $perPage, int $page): string
    {
        $normalizedFilters = [
            'global' => $filters['global'] ?? null,
            'name' => $filters['name'] ?? null,
            'email' => $filters['email'] ?? null,
            'phone' => $filters['phone'] ?? null,
            'is_active' => $filters['is_active'] ?? null,
        ];

        $payload = json_encode([
            'filters' => $normalizedFilters,
            'per_page' => $perPage,
            'page' => $page,
        ]);

        return 'companies.fetch.'.sha1((string) $payload);
    }

    private function paginateCacheTtlInSeconds(): int
    {
        return max((int) config('cache.companies_ttl', 300), 1);
    }

    private function flushListCache(): void
    {
        $this->cacheService->forgetAll($this->companiesCacheTag());
    }

    private function companiesCacheTag(): string
    {
        return Company::getTag();
    }
}

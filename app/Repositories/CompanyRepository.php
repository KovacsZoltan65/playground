<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

/**
 * A cégekhez tartozó összetettebb adat-hozzáférési logikát összefogó repository.
 *
 * A repository rétegben kezeli a listaoldali lekérdezést, a cache kulcsok felépítését
 * és a select opciók előállítását.
 */
class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }
    
    /**
     * A céglista szerveroldali szűrését, rendezését és opcionális cache-elését kezeli.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Company>
     */
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_companies', false);

        $sortField = $filters['sort_field'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $includeEmployeeCount = $this->shouldIncludeEmployeeCount($filters);
        $page = Paginator::resolveCurrentPage('page');
        $appendQuery = $this->buildAppendQuery($filters, $perPage, $page);

        $queryCallback = function () use (
            $filters,
            $perPage,
            $page,
            $appendQuery,
            $sortField,
            $sortDirection,
            $includeEmployeeCount
        ): LengthAwarePaginator {
            $query = $this->buildIndexQuery($filters, $includeEmployeeCount);
            $this->applySorting($query, $sortField, $sortDirection);

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

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

    /**
     * @param  array<int, int>  $ids
     * @return list<Company>
     */
    public function bulkSetActiveStatus(array $ids, bool $isActive): array
    {
        Company::query()
            ->whereIn('id', $ids)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);

        $this->flushListCache();

        /** @var array<int, Company> $updatedCompanies */
        $updatedCompanies = Company::query()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Company $company) => array_search($company->id, $ids, true))
            ->values()
            ->all();

        return $updatedCompanies;
    }

    /**
     * @return list<array{value:int,label:string}>
     */
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

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Company>
     */
    private function buildIndexQuery(array $filters = [], bool $includeEmployeeCount = false): Builder
    {
        $search = $this->normalizeSearchTerm($filters['global'] ?? null);
        $name = $this->normalizeSearchTerm($filters['name'] ?? null);
        $email = $this->normalizeSearchTerm($filters['email'] ?? null);
        $phone = $this->normalizeSearchTerm($filters['phone'] ?? null);
        $isActive = $filters['is_active'] ?? null;

        $query = Company::query();

        // Az alkalmazotti darabszámot csak akkor számoljuk ki, ha a kliens tényleg használja.
        if ($includeEmployeeCount) {
            $query->withCount('employees');
        }

        // A globális keresés rugalmas marad, ezért itt továbbra is contains minta fut.
        if ($search !== null) {
            $containsPattern = $this->buildContainsLikePattern($search);

            $query->where(function (Builder $companyQuery) use ($containsPattern): void {
                $companyQuery
                    ->where('name', 'like', $containsPattern)
                    ->orWhere('email', 'like', $containsPattern)
                    ->orWhere('phone', 'like', $containsPattern);
            });
        }

        // Az oszlopszűrők prefix keresést használnak, hogy jobban együtt tudjanak működni az indexekkel.
        if ($name !== null) {
            $query->where('name', 'like', $this->buildPrefixLikePattern($name));
        }

        if ($email !== null) {
            $query->where('email', 'like', $this->buildPrefixLikePattern($email));
        }

        if ($phone !== null) {
            $query->where('phone', 'like', $this->buildPrefixLikePattern($phone));
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, scalar|null>
     */
    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'name' => $filters['name'] ?? null,
            'email' => $filters['email'] ?? null,
            'phone' => $filters['phone'] ?? null,
            'is_active' => $filters['is_active'] ?? null,
            'include_employee_count' => $filters['include_employee_count'] ?? false,
            'sort_field' => $filters['sort_field'] ?? 'name',
            'sort_direction' => $filters['sort_direction'] ?? 'asc',
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
            'include_employee_count' => $filters['include_employee_count'] ?? false,
            'sort_field' => $filters['sort_field'] ?? 'name',
            'sort_direction' => $filters['sort_direction'] ?? 'asc',
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

    /**
     * A kliens explicit jelzése alapján csak akkor számoljuk ki az employee countot,
     * ha a felhasználó tényleg megjeleníti vagy rendezéshez használja ezt az oszlopot.
     *
     * @param  array<string, mixed>  $filters
     */
    private function shouldIncludeEmployeeCount(array $filters): bool
    {
        if (($filters['sort_field'] ?? null) === 'employees_count') {
            return true;
        }

        return filter_var(
            $filters['include_employee_count'] ?? false,
            FILTER_VALIDATE_BOOL
        ) === true;
    }

    private function normalizeSearchTerm(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalizedValue = trim($value);

        return $normalizedValue === '' ? null : $normalizedValue;
    }

    private function buildPrefixLikePattern(string $value): string
    {
        return $this->escapeLikeValue($value).'%';
    }

    private function buildContainsLikePattern(string $value): string
    {
        return '%'.$this->escapeLikeValue($value).'%';
    }

    private function escapeLikeValue(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    /**
     * Csak a whitelistelt mezőkön enged rendezést, hogy a listaoldal kérésparaméterei biztonságosak maradjanak.
     *
     * @param  Builder<Company>  $query
     */
    private function applySorting(Builder $query, string $sortField, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        $sortableFields = [
            'name',
            'email',
            'phone',
            'employees_count',
            'is_active',
            'updated_at',
            'created_at',
        ];

        $field = in_array($sortField, $sortableFields, true) ? $sortField : 'name';

        $query->orderBy($field, $direction)->orderBy('id');
    }
}

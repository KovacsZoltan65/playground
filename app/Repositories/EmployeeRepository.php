<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }

    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_employees', false);

        $sortField = $filters['sort_field'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $page = Paginator::resolveCurrentPage('page');
        $appendQuery = $this->buildAppendQuery($filters, $perPage, $page);

        $queryCallback = function () use (
            $filters,
            $perPage,
            $page,
            $appendQuery,
            $sortField,
            $sortDirection
        ): LengthAwarePaginator {
            $query = $this->buildIndexQuery($filters);
            $this->applySorting($query, $sortField, $sortDirection);

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);
            $paginator->appends($appendQuery);

            return $paginator;
        };

        if (! $needCache) {
            return $queryCallback();
        }

        return $this->cacheService->remember(
            $this->employeesCacheTag(),
            $this->buildPaginateCacheKey($filters, $perPage, $page),
            $queryCallback,
            $this->paginateCacheTtlInSeconds(),
        );
    }

    public function create(array $attributes): Employee
    {
        $employee = Employee::query()->create($attributes);

        $this->flushListCache();

        return $employee;
    }

    public function update(Employee $employee, array $attributes): Employee
    {
        $employee->update($attributes);

        $this->flushListCache();

        return $employee->refresh()->load('company:id,name');
    }

    public function toggleActiveStatus(Employee $employee): Employee
    {
        $employee->update([
            'active' => ! $employee->active,
        ]);

        $this->flushListCache();

        return $employee->refresh()->load('company:id,name');
    }

    public function delete(Employee $employee): bool
    {
        $deleted = (bool) $employee->delete();

        if ($deleted) {
            $this->flushListCache();
        }

        return $deleted;
    }

    public function bulkDeleteByIds(array $ids): int
    {
        $deletedCount = Employee::query()
            ->whereIn('id', $ids)
            ->delete();

        if ($deletedCount > 0) {
            $this->flushListCache();
        }

        return $deletedCount;
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;
        $companyId = $filters['company_id'] ?? null;
        $name = $filters['name'] ?? null;
        $email = $filters['email'] ?? null;
        $active = $filters['active'] ?? null;

        return Employee::query()
            ->with('company:id,name')
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($employeeQuery) use ($searchTerm) {
                    $employeeQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->when($companyId, fn ($query, $value) => $query->where('company_id', $value))
            ->when($name, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->when($email, fn ($query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($active !== null, fn ($query) => $query->where('active', $active));
    }

    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'company_id' => $filters['company_id'] ?? null,
            'name' => $filters['name'] ?? null,
            'email' => $filters['email'] ?? null,
            'active' => $filters['active'] ?? null,
            'sort_field' => $filters['sort_field'] ?? 'name',
            'sort_direction' => $filters['sort_direction'] ?? 'asc',
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildPaginateCacheKey(array $filters, int $perPage, int $page): string
    {
        $payload = json_encode([
            'filters' => [
                'global' => $filters['global'] ?? null,
                'company_id' => $filters['company_id'] ?? null,
                'name' => $filters['name'] ?? null,
                'email' => $filters['email'] ?? null,
                'active' => $filters['active'] ?? null,
                'sort_field' => $filters['sort_field'] ?? 'name',
                'sort_direction' => $filters['sort_direction'] ?? 'asc',
            ],
            'per_page' => $perPage,
            'page' => $page,
        ]);

        return 'employees.paginate.'.sha1((string) $payload);
    }

    private function paginateCacheTtlInSeconds(): int
    {
        return max((int) config('cache.employees_ttl', 300), 1);
    }

    private function flushListCache(): void
    {
        $this->cacheService->forgetAll($this->employeesCacheTag());
    }

    private function employeesCacheTag(): string
    {
        return Employee::getTag();
    }

    private function applySorting(Builder $query, string $sortField, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        if ($sortField === 'company_name') {
            $query->join('companies', 'companies.id', '=', 'employees.company_id')
                ->orderBy('companies.name', $direction)
                ->select('employees.*');

            return;
        }

        $sortableFields = [
            'name',
            'email',
            'active',
            'updated_at',
            'created_at',
        ];

        $field = in_array($sortField, $sortableFields, true) ? $sortField : 'name';

        $query->orderBy($field, $direction);
    }
}

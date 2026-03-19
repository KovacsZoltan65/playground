<?php

namespace App\Repositories;

use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }

    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_roles', false);
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
            $this->rolesCacheTag(),
            $this->buildPaginateCacheKey($filters, $perPage, $page),
            $queryCallback,
            $this->paginateCacheTtlInSeconds(),
        );
    }

    public function create(array $attributes): Role
    {
        $role = Role::query()->create($attributes);

        $this->flushCaches();

        return $role;
    }

    public function update(Role $role, array $attributes): Role
    {
        $role->update($attributes);

        $this->flushCaches();

        return $role->refresh()->load('permissions:id,name,guard_name');
    }

    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $role->syncPermissions($permissionIds);

        $this->flushCaches();

        return $role->refresh()->load('permissions:id,name,guard_name');
    }

    public function delete(Role $role): bool
    {
        $deleted = (bool) $role->delete();

        if ($deleted) {
            $this->flushCaches();
        }

        return $deleted;
    }

    public function bulkDeleteByIds(array $ids): int
    {
        $deletedCount = Role::query()
            ->whereIn('id', $ids)
            ->delete();

        if ($deletedCount > 0) {
            $this->flushCaches();
        }

        return $deletedCount;
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;
        $name = $this->normalizeSearchTerm($filters['name'] ?? null);
        $guardName = $filters['guard_name'] ?? null;

        $query = Role::query()
            ->with('permissions:id,name,guard_name')
            ->withCount('permissions')
            ->when($search, fn (Builder $query, $value) => $query->whereLike(['name', 'guard_name'], $value))
            ->when($guardName, fn (Builder $query, $value) => $query->where('guard_name', $value));

        if ($name !== null) {
            $query->where('name', 'like', $this->buildPrefixLikePattern($name));
        }

        return $query;
    }

    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'name' => $filters['name'] ?? null,
            'guard_name' => $filters['guard_name'] ?? null,
            'sort_field' => $filters['sort_field'] ?? 'name',
            'sort_direction' => $filters['sort_direction'] ?? 'asc',
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildPaginateCacheKey(array $filters, int $perPage, int $page): string
    {
        return 'roles.paginate.'.sha1((string) json_encode([
            'filters' => [
                'global' => $filters['global'] ?? null,
                'name' => $filters['name'] ?? null,
                'guard_name' => $filters['guard_name'] ?? null,
                'sort_field' => $filters['sort_field'] ?? 'name',
                'sort_direction' => $filters['sort_direction'] ?? 'asc',
            ],
            'per_page' => $perPage,
            'page' => $page,
        ]));
    }

    private function paginateCacheTtlInSeconds(): int
    {
        return max((int) config('cache.roles_ttl', 300), 1);
    }

    private function flushCaches(): void
    {
        $this->cacheService->forgetAll($this->rolesCacheTag());
        $this->cacheService->forgetAll($this->permissionsCacheTag());
    }

    private function rolesCacheTag(): string
    {
        return 'roles';
    }

    private function permissionsCacheTag(): string
    {
        return 'permissions';
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

    private function escapeLikeValue(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function applySorting(Builder $query, string $sortField, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        $sortableFields = [
            'name',
            'guard_name',
            'permissions_count',
            'updated_at',
            'created_at',
        ];

        $field = in_array($sortField, $sortableFields, true) ? $sortField : 'name';

        $query->orderBy($field, $direction)->orderBy('id');
    }
}

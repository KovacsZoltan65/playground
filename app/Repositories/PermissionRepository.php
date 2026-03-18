<?php

namespace App\Repositories;

use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Spatie\Permission\Models\Permission;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }

    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_permissions', false);
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
            $this->permissionsCacheTag(),
            $this->buildPaginateCacheKey($filters, $perPage, $page),
            $queryCallback,
            $this->paginateCacheTtlInSeconds(),
        );
    }

    public function create(array $attributes): Permission
    {
        $permission = Permission::query()->create($attributes);

        $this->flushCaches();

        return $permission;
    }

    public function update(Permission $permission, array $attributes): Permission
    {
        $permission->update($attributes);

        $this->flushCaches();

        return $permission->refresh();
    }

    public function delete(Permission $permission): bool
    {
        $deleted = (bool) $permission->delete();

        if ($deleted) {
            $this->flushCaches();
        }

        return $deleted;
    }

    public function bulkDeleteByIds(array $ids): int
    {
        $deletedCount = Permission::query()
            ->whereIn('id', $ids)
            ->delete();

        if ($deletedCount > 0) {
            $this->flushCaches();
        }

        return $deletedCount;
    }

    public function optionsForAssignment(string $guardName): array
    {
        return Permission::query()
            ->where('guard_name', $guardName)
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name'])
            ->map(fn (Permission $permission) => [
                'value' => $permission->id,
                'label' => $permission->name,
                'group' => str($permission->name)->before('.')->headline()->toString(),
            ])
            ->values()
            ->all();
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;
        $name = $filters['name'] ?? null;
        $guardName = $filters['guard_name'] ?? null;

        return Permission::query()
            ->withCount('roles')
            ->when($search, fn (Builder $query, $value) => $query->whereLike(['name', 'guard_name'], $value))
            ->when($name, fn (Builder $query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->when($guardName, fn (Builder $query, $value) => $query->where('guard_name', $value));
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
        return 'permissions.paginate.'.sha1((string) json_encode([
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
        return max((int) config('cache.permissions_ttl', 300), 1);
    }

    private function flushCaches(): void
    {
        $this->cacheService->forgetAll($this->permissionsCacheTag());
        $this->cacheService->forgetAll($this->rolesCacheTag());
    }

    private function permissionsCacheTag(): string
    {
        return 'permissions';
    }

    private function rolesCacheTag(): string
    {
        return 'roles';
    }

    private function applySorting(Builder $query, string $sortField, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        $sortableFields = [
            'name',
            'guard_name',
            'roles_count',
            'updated_at',
            'created_at',
        ];

        $field = in_array($sortField, $sortableFields, true) ? $sortField : 'name';

        $query->orderBy($field, $direction)->orderBy('id');
    }
}

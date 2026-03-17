<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserTemporaryPermission;
use App\Repositories\Contracts\UserTemporaryPermissionRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Spatie\Permission\Models\Permission;

class UserTemporaryPermissionRepository implements UserTemporaryPermissionRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }

    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_user_temporary_permissions', false);

        $page = Paginator::resolveCurrentPage('page');
        $appendQuery = $this->buildAppendQuery($filters, $perPage, $page);

        $queryCallback = function () use ($filters, $perPage, $page, $appendQuery): LengthAwarePaginator {
            $paginator = $this->buildIndexQuery($filters)
                ->orderByDesc('starts_at')
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

            $paginator->appends($appendQuery);

            return $paginator;
        };

        if (! $needCache) {
            return $queryCallback();
        }

        return $this->cacheService->remember(
            $this->cacheTag(),
            $this->buildPaginateCacheKey($filters, $perPage, $page),
            $queryCallback,
            max((int) config('cache.user_temporary_permissions_ttl', 300), 1),
        );
    }

    public function create(array $attributes): UserTemporaryPermission
    {
        $assignment = UserTemporaryPermission::query()->create($attributes);

        $this->flushListCache();

        return $assignment->load(['user:id,name', 'permission:id,name']);
    }

    public function update(UserTemporaryPermission $assignment, array $attributes): UserTemporaryPermission
    {
        $assignment->update($attributes);

        $this->flushListCache();

        return $assignment->refresh()->load(['user:id,name', 'permission:id,name']);
    }

    public function delete(UserTemporaryPermission $assignment): bool
    {
        $deleted = (bool) $assignment->delete();

        if ($deleted) {
            $this->flushListCache();
        }

        return $deleted;
    }

    public function bulkDeleteByIds(array $ids): int
    {
        $deletedCount = UserTemporaryPermission::query()
            ->whereIn('id', $ids)
            ->delete();

        if ($deletedCount > 0) {
            $this->flushListCache();
        }

        return $deletedCount;
    }

    public function userOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'value' => $user->id,
                'label' => $user->email ? sprintf('%s (%s)', $user->name, $user->email) : $user->name,
            ])
            ->values()
            ->all();
    }

    public function permissionOptions(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $permission) => [
                'value' => $permission->id,
                'label' => $permission->name,
            ])
            ->values()
            ->all();
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $permissionId = $filters['permission_id'] ?? null;
        $status = $filters['status'] ?? null;

        return UserTemporaryPermission::query()
            ->with([
                'user:id,name,email',
                'permission:id,name',
            ])
            ->when($search, function (Builder $query, string $searchTerm): void {
                $query->where(function (Builder $innerQuery) use ($searchTerm): void {
                    $innerQuery
                        ->whereHas('user', function (Builder $userQuery) use ($searchTerm): void {
                            $userQuery
                                ->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('email', 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('permission', function (Builder $permissionQuery) use ($searchTerm): void {
                            $permissionQuery->where('name', 'like', "%{$searchTerm}%");
                        })
                        ->orWhere('reason', 'like', "%{$searchTerm}%");
                });
            })
            ->when($userId, fn (Builder $query, $value) => $query->where('user_id', (int) $value))
            ->when($permissionId, fn (Builder $query, $value) => $query->where('permission_id', (int) $value))
            ->when($status, fn (Builder $query, string $value) => $query->withStatus($value));
    }

    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'user_id' => $filters['user_id'] ?? null,
            'permission_id' => $filters['permission_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildPaginateCacheKey(array $filters, int $perPage, int $page): string
    {
        $payload = json_encode([
            'filters' => [
                'global' => $filters['global'] ?? null,
                'user_id' => $filters['user_id'] ?? null,
                'permission_id' => $filters['permission_id'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'per_page' => $perPage,
            'page' => $page,
        ]);

        return 'user-temporary-permissions.fetch.'.sha1((string) $payload);
    }

    private function flushListCache(): void
    {
        $this->cacheService->forgetAll($this->cacheTag());
    }

    private function cacheTag(): string
    {
        return UserTemporaryPermission::getTag();
    }
}

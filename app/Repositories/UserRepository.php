<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Spatie\Permission\Models\Role;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }

    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_users', false);
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
            $this->usersCacheTag(),
            $this->buildPaginateCacheKey($filters, $perPage, $page),
            $queryCallback,
            $this->paginateCacheTtlInSeconds(),
        );
    }

    public function create(array $attributes): User
    {
        $user = User::query()->create($attributes);

        $this->flushCaches();

        return $user->load('roles:id,name,guard_name');
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        $this->flushCaches();

        return $user->refresh()->load('roles:id,name,guard_name');
    }

    public function syncRoles(User $user, array $roleIds): User
    {
        $user->syncRoles(
            Role::query()
                ->whereIn('id', $roleIds)
                ->get()
        );

        $this->flushCaches();

        return $user->refresh()->load('roles:id,name,guard_name');
    }

    public function delete(User $user): bool
    {
        $user->syncRoles([]);
        $user->syncPermissions([]);

        $deleted = (bool) $user->delete();

        if ($deleted) {
            $this->flushCaches();
        }

        return $deleted;
    }

    public function bulkDeleteByIds(array $ids): int
    {
        $deletedCount = 0;

        User::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(function (User $user) use (&$deletedCount): void {
                if ($this->delete($user)) {
                    $deletedCount++;
                }
            });

        return $deletedCount;
    }

    public function roleOptions(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'value' => $role->id,
                'label' => $role->name,
            ])
            ->values()
            ->all();
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;
        $name = $filters['name'] ?? null;
        $email = $filters['email'] ?? null;
        $roleId = $filters['role_id'] ?? null;

        return User::query()
            ->with('roles:id,name,guard_name')
            ->withCount('roles')
            ->when($search, fn (Builder $query, $value) => $this->applyGlobalSearch($query, (string) $value))
            ->when($name, fn (Builder $query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->when($email, fn (Builder $query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($roleId, fn (Builder $query, $value) => $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('roles.id', (int) $value)));
    }

    private function applyGlobalSearch(Builder $query, string $search): Builder
    {
        $terms = preg_split('/\s+/', trim($search)) ?: [$search];

        return $query->where(function (Builder $searchQuery) use ($terms): void {
            foreach ($terms as $term) {
                $searchQuery->where(function (Builder $termQuery) use ($term): void {
                    $termQuery
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'like', "%{$term}%"));
                });
            }
        });
    }

    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'name' => $filters['name'] ?? null,
            'email' => $filters['email'] ?? null,
            'role_id' => $filters['role_id'] ?? null,
            'sort_field' => $filters['sort_field'] ?? 'name',
            'sort_direction' => $filters['sort_direction'] ?? 'asc',
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildPaginateCacheKey(array $filters, int $perPage, int $page): string
    {
        return 'users.paginate.'.sha1((string) json_encode([
            'filters' => [
                'global' => $filters['global'] ?? null,
                'name' => $filters['name'] ?? null,
                'email' => $filters['email'] ?? null,
                'role_id' => $filters['role_id'] ?? null,
                'sort_field' => $filters['sort_field'] ?? 'name',
                'sort_direction' => $filters['sort_direction'] ?? 'asc',
            ],
            'per_page' => $perPage,
            'page' => $page,
        ]));
    }

    private function paginateCacheTtlInSeconds(): int
    {
        return max((int) config('cache.users_ttl', 300), 1);
    }

    private function flushCaches(): void
    {
        $this->cacheService->forgetAll($this->usersCacheTag());
    }

    private function usersCacheTag(): string
    {
        return 'users';
    }

    private function applySorting(Builder $query, string $sortField, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        $sortableFields = [
            'name',
            'email',
            'roles_count',
            'email_verified_at',
            'updated_at',
            'created_at',
        ];

        $field = in_array($sortField, $sortableFields, true) ? $sortField : 'name';

        $query->orderBy($field, $direction)->orderBy('id');
    }
}

<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator;

    public function create(array $attributes): User;

    public function update(User $user, array $attributes): User;

    public function syncRoles(User $user, array $roleIds): User;

    public function delete(User $user): bool;

    public function bulkDeleteByIds(array $ids): int;

    public function roleOptions(): array;
}

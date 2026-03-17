<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator;

    public function create(array $attributes): Role;

    public function update(Role $role, array $attributes): Role;

    public function syncPermissions(Role $role, array $permissionIds): Role;

    public function delete(Role $role): bool;

    public function bulkDeleteByIds(array $ids): int;
}

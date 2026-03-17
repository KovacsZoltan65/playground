<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

interface PermissionRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator;

    public function create(array $attributes): Permission;

    public function update(Permission $permission, array $attributes): Permission;

    public function delete(Permission $permission): bool;

    public function bulkDeleteByIds(array $ids): int;

    public function optionsForAssignment(string $guardName): array;
}

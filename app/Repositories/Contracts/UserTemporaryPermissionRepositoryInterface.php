<?php

namespace App\Repositories\Contracts;

use App\Models\UserTemporaryPermission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserTemporaryPermissionRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator;

    public function create(array $attributes): UserTemporaryPermission;

    public function update(UserTemporaryPermission $assignment, array $attributes): UserTemporaryPermission;

    public function delete(UserTemporaryPermission $assignment): bool;

    public function bulkDeleteByIds(array $ids): int;

    public function userOptions(): array;

    public function permissionOptions(): array;
}

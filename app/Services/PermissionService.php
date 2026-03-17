<?php

namespace App\Services;

use App\Data\PermissionData;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions,
    ) {
    }

    public function listForIndex(array $filters = [], int $perPage = 10): array
    {
        $paginator = $this->permissions->paginateForIndex($filters, $perPage);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (Permission $permission) => PermissionData::fromModel($permission))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function show(Permission $permission): PermissionData
    {
        return PermissionData::fromModel($permission);
    }

    public function create(PermissionData $permissionData): PermissionData
    {
        return PermissionData::fromModel(
            $this->permissions->create($permissionData->toRepositoryAttributes()),
        );
    }

    public function update(Permission $permission, PermissionData $permissionData): PermissionData
    {
        return PermissionData::fromModel(
            $this->permissions->update($permission, $permissionData->toRepositoryAttributes()),
        );
    }

    public function delete(Permission $permission): bool
    {
        return $this->permissions->delete($permission);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->permissions->bulkDeleteByIds($ids);
    }
}

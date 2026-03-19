<?php

namespace App\Services;

use App\Data\RoleData;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions,
    ) {
    }

    public function listForIndex(array $filters = [], int $perPage = 10): array
    {
        /** @var LengthAwarePaginator<int, Role> $paginator */
        $paginator = $this->roles->paginateForIndex($filters, $perPage);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (Role $role) => RoleData::fromModel($role))
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

    public function show(Role $role): RoleData
    {
        return RoleData::fromModel($role);
    }

    public function create(RoleData $roleData): RoleData
    {
        $role = DB::transaction(function () use ($roleData): Role {
            $role = $this->roles->create($roleData->toRepositoryAttributes());

            return $this->roles->syncPermissions($role, $roleData->permissionIds());
        });

        return RoleData::fromModel($role);
    }

    public function update(Role $role, RoleData $roleData): RoleData
    {
        $updatedRole = DB::transaction(function () use ($role, $roleData): Role {
            $updatedRole = $this->roles->update($role, $roleData->toRepositoryAttributes());

            return $this->roles->syncPermissions($updatedRole, $roleData->permissionIds());
        });

        return RoleData::fromModel($updatedRole);
    }

    public function delete(Role $role): bool
    {
        return $this->roles->delete($role);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->roles->bulkDeleteByIds($ids);
    }

    public function permissionOptions(string $guardName): array
    {
        return $this->permissions->optionsForAssignment($guardName);
    }
}

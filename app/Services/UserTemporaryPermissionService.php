<?php

namespace App\Services;

use App\Data\UserTemporaryPermissionData;
use App\Models\UserTemporaryPermission;
use App\Repositories\Contracts\UserTemporaryPermissionRepositoryInterface;

class UserTemporaryPermissionService
{
    public function __construct(
        private readonly UserTemporaryPermissionRepositoryInterface $assignments,
    ) {
    }

    public function listForIndex(array $filters = [], int $perPage = 10): array
    {
        $paginator = $this->assignments->paginateForIndex($filters, $perPage);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (UserTemporaryPermission $assignment) => UserTemporaryPermissionData::fromModel($assignment))
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

    public function show(UserTemporaryPermission $assignment): UserTemporaryPermissionData
    {
        return UserTemporaryPermissionData::fromModel($assignment);
    }

    public function create(UserTemporaryPermissionData $data): UserTemporaryPermissionData
    {
        $assignment = $this->assignments->create([
            ...$data->toRepositoryAttributes(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return UserTemporaryPermissionData::fromModel($assignment);
    }

    public function update(UserTemporaryPermission $assignment, UserTemporaryPermissionData $data): UserTemporaryPermissionData
    {
        $updatedAssignment = $this->assignments->update($assignment, [
            ...$data->toRepositoryAttributes(),
            'updated_by' => auth()->id(),
        ]);

        return UserTemporaryPermissionData::fromModel($updatedAssignment);
    }

    public function delete(UserTemporaryPermission $assignment): bool
    {
        return $this->assignments->delete($assignment);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->assignments->bulkDeleteByIds($ids);
    }

    public function userOptions(): array
    {
        return $this->assignments->userOptions();
    }

    public function permissionOptions(): array
    {
        return $this->assignments->permissionOptions();
    }

    public function userEffectivePermissionIds(): array
    {
        return $this->assignments->userEffectivePermissionIds();
    }
}

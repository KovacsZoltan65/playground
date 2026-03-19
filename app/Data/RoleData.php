<?php

namespace App\Data;

use App\Support\Permissions\PermissionGuards;
use App\Support\Validation\SharedLaravelValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\Permission\Models\Role;

/**
 * A szerepkörök szerkesztéséhez és listázásához használt DTO.
 */
class RoleData extends Data
{
    /**
     * @param  array<int, int>  $permission_ids
     * @param  array<int, string>  $permission_names
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public string $guard_name,
        public array $permission_ids = [],
        public array $permission_names = [],
        public int $permissions_count = 0,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromModel(Role $role): self
    {
        $role->loadMissing('permissions:id,name,guard_name');

        return new self(
            id: $role->id,
            name: $role->name,
            guard_name: $role->guard_name,
            permission_ids: $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            permission_names: $role->permissions->pluck('name')->values()->all(),
            permissions_count: (int) ($role->permissions_count ?? $role->permissions->count()),
            created_at: $role->created_at?->toDateTimeString(),
            updated_at: $role->updated_at?->toDateTimeString(),
        );
    }

    public static function fromRequest(Request $request, ?Role $role = null): self
    {
        return self::validateAndCreate([
            'id' => $role?->id,
            'name' => trim((string) $request->input('name')),
            'guard_name' => $request->input('guard_name', PermissionGuards::default()),
            'permission_ids' => collect($request->input('permission_ids', []))
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<int, int>
     */
    public static function validateBulkDeleteIds(Request $request): array
    {
        return Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ])->validate()['ids'];
    }

    public static function rules(?ValidationContext $context = null): array
    {
        /** @var Role|null $role */
        $role = request()->route('role');

        return array_merge(
            SharedLaravelValidationRules::for('role', [
                'role' => $role,
                'guard_name' => request()->input('guard_name', PermissionGuards::default()),
            ]),
            [
                'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
            ],
        );
    }

    /**
     * @return array{name:string,guard_name:string}
     */
    public function toRepositoryAttributes(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ];
    }

    /**
     * @return array<int, int>
     */
    public function permissionIds(): array
    {
        return $this->permission_ids;
    }
}

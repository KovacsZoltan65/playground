<?php

namespace App\Data;

use App\Support\Permissions\PermissionGuards;
use App\Support\Validation\SharedLaravelValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\Permission\Models\Permission;

class PermissionData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $guard_name,
        public int $roles_count = 0,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromModel(Permission $permission): self
    {
        return new self(
            id: $permission->id,
            name: $permission->name,
            guard_name: $permission->guard_name,
            roles_count: (int) ($permission->roles_count ?? 0),
            created_at: $permission->created_at?->toDateTimeString(),
            updated_at: $permission->updated_at?->toDateTimeString(),
        );
    }

    public static function fromRequest(Request $request, ?Permission $permission = null): self
    {
        return self::validateAndCreate([
            'id' => $permission?->id,
            'name' => trim((string) $request->input('name')),
            'guard_name' => $request->input('guard_name', PermissionGuards::default()),
        ]);
    }

    public static function validateBulkDeleteIds(Request $request): array
    {
        return Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ])->validate()['ids'];
    }

    public static function rules(?ValidationContext $context = null): array
    {
        /** @var Permission|null $permission */
        $permission = request()->route('permission');

        return SharedLaravelValidationRules::for('permission', [
            'permission' => $permission,
            'guard_name' => request()->input('guard_name', PermissionGuards::default()),
        ]);
    }

    public function toRepositoryAttributes(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ];
    }
}

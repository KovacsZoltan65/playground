<?php

namespace App\Data;

use App\Models\UserTemporaryPermission;
use App\Support\Permissions\PermissionGuards;
use App\Support\Validation\SharedLaravelValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\Permission\Models\Permission;

/**
 * Az ideiglenes jogosultság-hozzárendelések űrlap- és lista DTO-ja.
 */
class UserTemporaryPermissionData extends Data
{
    public function __construct(
        public ?int $id,
        public int $user_id,
        public int $permission_id,
        public string $user_name = '',
        public string $permission_name = '',
        public string $starts_at = '',
        public string $ends_at = '',
        public ?string $reason = null,
        public string $status = 'active',
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromModel(UserTemporaryPermission $assignment): self
    {
        $assignment->loadMissing(['user:id,name', 'permission:id,name']);

        return new self(
            id: $assignment->id,
            user_id: $assignment->user_id,
            permission_id: $assignment->permission_id,
            user_name: $assignment->user->name,
            permission_name: $assignment->permission->name,
            starts_at: $assignment->starts_at->format('Y-m-d\TH:i'),
            ends_at: $assignment->ends_at->format('Y-m-d\TH:i'),
            reason: $assignment->reason,
            status: $assignment->statusLabel(),
            created_at: $assignment->created_at?->toDateTimeString(),
            updated_at: $assignment->updated_at?->toDateTimeString(),
        );
    }

    public static function fromRequest(Request $request, ?UserTemporaryPermission $assignment = null): self
    {
        return self::validateAndCreate([
            'id' => $assignment?->id,
            'user_id' => (int) $request->input('user_id'),
            'permission_id' => (int) $request->input('permission_id'),
            'starts_at' => (string) $request->input('starts_at'),
            'ends_at' => (string) $request->input('ends_at'),
            'reason' => $request->filled('reason') ? trim((string) $request->input('reason')) : null,
        ]);
    }

    /**
     * @return array<int, int>
     */
    public static function validateBulkDeleteIds(Request $request): array
    {
        return Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:user_temporary_permissions,id'],
        ])->validate()['ids'];
    }

    public static function rules(?ValidationContext $context = null): array
    {
        /** @var UserTemporaryPermission|null $assignment */
        $assignment = request()->route('userTemporaryPermission');

        return array_merge(
            SharedLaravelValidationRules::for('userTemporaryPermission', [
                'userTemporaryPermission' => $assignment,
            ]),
            [
                'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
                'permission_id' => [
                    'required',
                    'integer',
                    'exists:permissions,id',
                    function (string $attribute, mixed $value, \Closure $fail) use ($assignment): void {
                        $permission = Permission::query()->find($value);

                        if (! $permission || $permission->guard_name !== PermissionGuards::default()) {
                            $fail(__('Temporary permissions can only target web guard permissions.'));

                            return;
                        }

                        $userId = (int) request()->input('user_id');
                        $startsAt = request()->input('starts_at');
                        $endsAt = request()->input('ends_at');

                        if (! $userId || ! $startsAt || ! $endsAt) {
                            return;
                        }

                        $overlapQuery = UserTemporaryPermission::query()
                            ->where('user_id', $userId)
                            ->where('permission_id', (int) $value)
                            ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($startsAt, $endsAt): void {
                                $query
                                    ->where('starts_at', '<=', $endsAt)
                                    ->where('ends_at', '>=', $startsAt);
                            });

                        if ($assignment) {
                            $overlapQuery->whereKeyNot($assignment->getKey());
                        }

                        if ($overlapQuery->exists()) {
                            $fail(__('A temporary permission assignment already overlaps with this time range.'));
                        }
                    },
                ],
            ],
        );
    }

    /**
     * @return array{user_id:int,permission_id:int,starts_at:string,ends_at:string,reason:?string}
     */
    public function toRepositoryAttributes(): array
    {
        return [
            'user_id' => $this->user_id,
            'permission_id' => $this->permission_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'reason' => $this->reason,
        ];
    }
}

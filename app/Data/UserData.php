<?php

namespace App\Data;

use App\Models\User;
use App\Support\Validation\SharedLaravelValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UserData extends Data
{
    /**
     * @param  array<int, int>  $role_ids
     * @param  array<int, string>  $role_names
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?string $password_confirmation = null,
        public array $role_ids = [],
        public array $role_names = [],
        public int $roles_count = 0,
        public ?string $email_verified_at = null,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromModel(User $user): self
    {
        $user->loadMissing('roles:id,name,guard_name');

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            password: null,
            password_confirmation: null,
            role_ids: $user->roles->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            role_names: $user->roles->pluck('name')->values()->all(),
            roles_count: (int) ($user->roles_count ?? $user->roles->count()),
            email_verified_at: $user->email_verified_at?->toDateTimeString(),
            created_at: $user->created_at?->toDateTimeString(),
            updated_at: $user->updated_at?->toDateTimeString(),
        );
    }

    public static function fromRequest(Request $request, ?User $user = null): self
    {
        $password = $request->input('password');
        $passwordConfirmation = $request->input('password_confirmation');

        return self::validateAndCreate([
            'id' => $user?->id,
            'name' => trim((string) $request->input('name')),
            'email' => trim((string) $request->input('email')),
            'password' => filled($password) ? (string) $password : null,
            'password_confirmation' => filled($passwordConfirmation) ? (string) $passwordConfirmation : null,
            'role_ids' => collect($request->input('role_ids', []))
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    public static function validateBulkDeleteIds(Request $request): array
    {
        return Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ])->validate()['ids'];
    }

    public static function rules(?ValidationContext $context = null): array
    {
        /** @var User|null $user */
        $user = request()->route('user');

        $rules = SharedLaravelValidationRules::for('user', [
            'user' => $user,
        ]);

        $rules['role_ids.*'] = ['integer', 'distinct', 'exists:roles,id'];

        $passwordRules = ['nullable', 'confirmed', Password::defaults()];

        if ($user === null) {
            $passwordRules = ['required', 'confirmed', Password::defaults()];
        }

        $rules['password'] = $passwordRules;
        $rules['password_confirmation'] = [$user === null ? 'required' : 'nullable'];

        return $rules;
    }

    public function toRepositoryAttributes(): array
    {
        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password !== null && $this->password !== '') {
            $attributes['password'] = $this->password;
        }

        return $attributes;
    }

    public function roleIds(): array
    {
        return $this->role_ids;
    }
}

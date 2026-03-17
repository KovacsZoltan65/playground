<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable {
        HasRoles::hasPermissionTo as protected spatieHasPermissionTo;
        HasRoles::getAllPermissions as protected spatieGetAllPermissions;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function temporaryPermissions(): HasMany
    {
        return $this->hasMany(UserTemporaryPermission::class);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($this->spatieHasPermissionTo($permission, $guardName)) {
            return true;
        }

        $resolvedPermission = $this->filterPermission($permission, $guardName);

        return $this->temporaryPermissions()
            ->activeAt()
            ->where('permission_id', $resolvedPermission->getKey())
            ->exists();
    }

    public function getAllPermissions(): Collection
    {
        $permanentPermissions = $this->spatieGetAllPermissions();

        $temporaryPermissions = $this->temporaryPermissions()
            ->activeAt()
            ->with('permission')
            ->get()
            ->pluck('permission')
            ->filter();

        return $permanentPermissions
            ->merge($temporaryPermissions)
            ->unique(fn ($permission) => $permission->getKey())
            ->values();
    }
}

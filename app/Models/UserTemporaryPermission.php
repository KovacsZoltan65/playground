<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission;

/**
 * Időablakhoz kötött, felhasználószintű ideiglenes jogosultságokat reprezentáló modell.
 */
class UserTemporaryPermission extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'permission_id',
        'starts_at',
        'ends_at',
        'reason',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @param  \DateTimeInterface|string|null  $moment
     * @return Builder<self>
     */
    public function scopeActiveAt(Builder $query, $moment = null): Builder
    {
        $moment ??= now();

        return $query
            ->where('starts_at', '<=', $moment)
            ->where('ends_at', '>=', $moment);
    }

    /**
     * @param  Builder<self>  $query
     * @param  \DateTimeInterface|string|null  $moment
     * @return Builder<self>
     */
    public function scopeUpcomingAt(Builder $query, $moment = null): Builder
    {
        $moment ??= now();

        return $query->where('starts_at', '>', $moment);
    }

    /**
     * @param  Builder<self>  $query
     * @param  \DateTimeInterface|string|null  $moment
     * @return Builder<self>
     */
    public function scopeExpiredAt(Builder $query, $moment = null): Builder
    {
        $moment ??= now();

        return $query->where('ends_at', '<', $moment);
    }

    /**
     * @param  Builder<self>  $query
     * @param  'active'|'upcoming'|'expired'|null  $status
     * @param  \DateTimeInterface|string|null  $moment
     * @return Builder<self>
     */
    public function scopeWithStatus(Builder $query, ?string $status, $moment = null): Builder
    {
        return match ($status) {
            'active' => $query->activeAt($moment),
            'upcoming' => $query->upcomingAt($moment),
            'expired' => $query->expiredAt($moment),
            default => $query,
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Permission, $this>
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLabel(): string
    {
        $now = now();

        if ($this->starts_at->isFuture()) {
            return 'upcoming';
        }

        if ($this->ends_at->isPast()) {
            return 'expired';
        }

        if ($this->starts_at <= $now && $this->ends_at >= $now) {
            return 'active';
        }

        return 'expired';
    }

    protected string $logName = 'user_temporary_permissions';

    public static function getTag(): string
    {
        return (new self())->logName;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->logName)
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

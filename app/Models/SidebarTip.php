<?php

namespace App\Models;

use Database\Factories\SidebarTipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Egy adott oldalhoz tartozó egyedi sidebar tippet reprezentáló modell.
 */
class SidebarTip extends Model
{
    /** @use HasFactory<SidebarTipFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'sidebar_tip_page_id',
        'content',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', APP_ACTIVE);
    }

    /**
     * @return BelongsTo<SidebarTipPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(SidebarTipPage::class, 'sidebar_tip_page_id');
    }
    /** Activity log channel used for sidebar tip changes. */
    protected static string $logName = 'sidebar_tips';

    public static function getTag(): string
    {
        return self::$logName;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(self::$logName)
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

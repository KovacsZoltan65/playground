<?php

namespace App\Models;

use Database\Factories\SidebarTipPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Az oldalszintű sidebar tipp konfigurációt és a hozzá tartozó tippeket összefogó modell.
 */
class SidebarTipPage extends Model
{
    /** @use HasFactory<SidebarTipPageFactory> */
    use HasFactory, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'page_component',
        'is_visible',
        'rotation_interval_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'rotation_interval_seconds' => 'integer',
        ];
    }

    /**
     * @return HasMany<SidebarTip, $this>
     */
    public function tips(): HasMany
    {
        return $this->hasMany(SidebarTip::class)->orderBy('sort_order')->orderBy('id');
    }
    /** Activity log channel used for sidebar tip page changes. */
    protected string $logName = 'sidebar_tip_pages';
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

<?php

namespace App\Models;

use Database\Factories\SidebarTipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SidebarTip extends Model
{
    /** @use HasFactory<SidebarTipFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'sidebar_tip_page_id',
        'content',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', APP_ACTIVE);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SidebarTipPage::class, 'sidebar_tip_page_id');
    }
    
    /*
     * ========================= LOGOLÁS =========================
     */

    protected static string $logName = 'sidebar_tips';
    
    public static function getTag(): string
    {
        //return (new self())->logName;
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
    
    /**
     * ===========================================================
     */
}

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

    public function page(): BelongsTo
    {
        return $this->belongsTo(SidebarTipPage::class, 'sidebar_tip_page_id');
    }

    protected static string $logName = 'sidebar_tips';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(self::$logName)
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

<?php

use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('activity-logs')
    ->name('activity-logs.')
    ->controller(ActivityLogController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index')->middleware('throttle:60,1');
        Route::get('/list', 'list')->name('list')->middleware('throttle:60,1');
    });

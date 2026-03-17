<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('permissions')
    ->name('permissions.')
    ->controller(PermissionController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index')->middleware('throttle:60,1');
        Route::get('/create', 'create')->name('create');
        Route::get('/list', 'list')->name('list')->middleware('throttle:60,1');
        Route::get('/{permission}/edit', 'edit')->name('edit');
        Route::get('/{permission}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy');
        Route::put('/{permission}', 'update')->name('update');
        Route::delete('/{permission}', 'destroy')->name('destroy');
    });

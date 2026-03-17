<?php

use App\Http\Controllers\UserTemporaryPermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('user-temporary-permissions')
    ->name('user-temporary-permissions.')
    ->controller(UserTemporaryPermissionController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index')->middleware('throttle:60,1');
        Route::get('/create', 'create')->name('create');
        Route::get('/list', 'list')->name('list')->middleware('throttle:60,1');
        Route::get('/{userTemporaryPermission}/edit', 'edit')->name('edit');
        Route::get('/{userTemporaryPermission}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy');
        Route::put('/{userTemporaryPermission}', 'update')->name('update');
        Route::delete('/{userTemporaryPermission}', 'destroy')->name('destroy');
    });

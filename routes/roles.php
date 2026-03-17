<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('roles')
    ->name('roles.')
    ->controller(RoleController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index')->middleware('throttle:60,1');
        Route::get('/create', 'create')->name('create');
        Route::get('/list', 'list')->name('list')->middleware('throttle:60,1');
        Route::get('/{role}/edit', 'edit')->name('edit');
        Route::get('/{role}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy');
        Route::put('/{role}', 'update')->name('update');
        Route::delete('/{role}', 'destroy')->name('destroy');
    });

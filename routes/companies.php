<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('companies')
    ->name('companies.')
    ->controller(CompanyController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index')->middleware('throttle:60,1');
        Route::get('/create', 'create')->name('create');
        Route::get('/list', 'list')->name('list')->middleware('throttle:60,1');
        Route::get('/{company}/edit', 'edit')->name('edit');
        Route::get('/{company}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::patch('/bulk-activate', 'bulkActivate')->name('bulk-activate');
        Route::patch('/bulk-deactivate', 'bulkDeactivate')->name('bulk-deactivate');
        Route::patch('/{company}/toggle-active', 'toggleActiveStatus')->name('toggle-active');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy');
        Route::put('/{company}', 'update')->name('update');
        Route::delete('/{company}', 'destroy')->name('destroy');
    });

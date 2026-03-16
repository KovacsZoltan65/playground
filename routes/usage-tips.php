<?php

use App\Http\Controllers\SidebarTipPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('usage-tips')
    ->name('usage-tips.')
    ->controller(SidebarTipPageController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/list', 'list')->name('list');
        Route::get('/{sidebarTipPage}/edit', 'edit')->name('edit');
        Route::get('/{sidebarTipPage}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::put('/{sidebarTipPage}', 'update')->name('update');
        Route::delete('/{sidebarTipPage}', 'destroy')->name('destroy');
    });

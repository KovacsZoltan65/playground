<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index')->middleware('throttle:60,1');
        Route::get('/create', 'create')->name('create');
        Route::get('/list', 'list')->name('list')->middleware('throttle:60,1');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::get('/{user}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::post('/{user}/send-verification-email', 'sendVerificationEmail')
            ->name('send-verification-email')
            ->middleware('throttle:6,1');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });

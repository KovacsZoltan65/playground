<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('employees')
    ->name('employees.')
    ->controller(EmployeeController::class)
    ->group(function (): void {
        // Olvasás
        Route::get('/', 'index')->name('index')->middleware('throttle:60,1');
        Route::get('/create', 'create')->name('create')->middleware('throttle:60,1');
        Route::get('/list', 'list')->name('list')->middleware('throttle:60,1');
        Route::get('/{employee}/edit', 'edit')->name('edit')->middleware('throttle:60,1');
        Route::get('/{employee}', 'show')->name('show')->middleware('throttle:60,1');

        // Írás
        Route::post('/', 'store')->name('store')->middleware('throttle:20,1');
        Route::patch('/{employee}/toggle-active', 'toggleActiveStatus')->name('toggle-active')->middleware('throttle:10,1');
        Route::put('/{employee}', 'update')->name('update')->middleware('throttle:30,1');
        Route::delete('/{employee}', 'destroy')->name('destroy')->middleware('throttle:10,1');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy')->middleware('throttle:20,1');
    });

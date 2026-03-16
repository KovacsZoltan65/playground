<?php

use App\Http\Controllers\EmployeeController;
use App\Models\Employee;
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
        Route::patch('/bulk-activate', function () {
            abort_unless(request()->user()?->can('employees.update'), 403);

            $validated = request()->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:employees,id'],
            ]);

            Employee::query()
                ->whereIn('id', $validated['ids'])
                ->update([
                    'active' => true,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'count' => count($validated['ids']),
            ]);
        })->name('bulk-activate')->middleware('throttle:20,1');
        Route::patch('/bulk-deactivate', function () {
            abort_unless(request()->user()?->can('employees.update'), 403);

            $validated = request()->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:employees,id'],
            ]);

            Employee::query()
                ->whereIn('id', $validated['ids'])
                ->update([
                    'active' => false,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'count' => count($validated['ids']),
            ]);
        })->name('bulk-deactivate')->middleware('throttle:20,1');
        Route::patch('/{employee}/toggle-active', 'toggleActiveStatus')->name('toggle-active')->middleware('throttle:10,1');
        Route::put('/{employee}', 'update')->name('update')->middleware('throttle:30,1');
        Route::delete('/{employee}', 'destroy')->name('destroy')->middleware('throttle:10,1');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy')->middleware('throttle:20,1');
    });

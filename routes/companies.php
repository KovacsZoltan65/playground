<?php

use App\Http\Controllers\CompanyController;
use App\Models\Company;
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

        // BULK Activate
        Route::patch('/bulk-activate', function () {
            abort_unless(request()->user()?->can('companies.update'), 403);

            $validated = request()->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:companies,id'],
            ]);

            Company::query()
                ->whereIn('id', $validated['ids'])
                ->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'count' => count($validated['ids']),
            ]);
        })->name('bulk-activate');

        // BULK DEACTIVATE
        Route::patch('/bulk-deactivate', function () {
            abort_unless(request()->user()?->can('companies.update'), 403);

            $validated = request()->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:companies,id'],
            ]);

            Company::query()
                ->whereIn('id', $validated['ids'])
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'count' => count($validated['ids']),
            ]);
        })->name('bulk-deactivate');

        // TOGGLE ACTIVATE
        Route::patch('/{company}/toggle-active', 'toggleActiveStatus')->name('toggle-active');
        Route::delete('/', 'bulkDestroy')->name('bulk-destroy');
        Route::put('/{company}', 'update')->name('update');
        Route::delete('/{company}', 'destroy')->name('destroy');
    });

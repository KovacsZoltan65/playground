<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FrontendErrorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SidebarTipPageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/frontend-errors', [FrontendErrorController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('frontend-errors.store');

Route::middleware('auth')->group(function () {
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::get('/companies/list', [CompanyController::class, 'list'])->name('companies.list');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::patch('/companies/{company}/toggle-active', [CompanyController::class, 'toggleActiveStatus'])->name('companies.toggle-active');
    Route::delete('/companies', [CompanyController::class, 'bulkDestroy'])->name('companies.bulk-destroy');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    Route::get('/usage-tips', [SidebarTipPageController::class, 'index'])->name('usage-tips.index');
    Route::get('/usage-tips/create', [SidebarTipPageController::class, 'create'])->name('usage-tips.create');
    Route::get('/usage-tips/list', [SidebarTipPageController::class, 'list'])->name('usage-tips.list');
    Route::get('/usage-tips/{sidebarTipPage}/edit', [SidebarTipPageController::class, 'edit'])->name('usage-tips.edit');
    Route::get('/usage-tips/{sidebarTipPage}', [SidebarTipPageController::class, 'show'])->name('usage-tips.show');
    Route::post('/usage-tips', [SidebarTipPageController::class, 'store'])->name('usage-tips.store');
    Route::put('/usage-tips/{sidebarTipPage}', [SidebarTipPageController::class, 'update'])->name('usage-tips.update');
    Route::delete('/usage-tips/{sidebarTipPage}', [SidebarTipPageController::class, 'destroy'])->name('usage-tips.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

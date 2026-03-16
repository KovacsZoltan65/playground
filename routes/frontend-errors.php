<?php

use App\Http\Controllers\FrontendErrorController;
use Illuminate\Support\Facades\Route;

Route::post('/frontend-errors', [FrontendErrorController::class, 'store'])
    ->name('frontend-errors.store');

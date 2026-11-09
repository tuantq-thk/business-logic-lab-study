<?php

use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\LearningShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/home', [LearningShellController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'can:manage-catalog'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::patch('/catalog', [CatalogController::class, 'update'])->name('catalog.update');
    });

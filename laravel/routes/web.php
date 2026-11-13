<?php

use App\Features\StateTransition\Http\Controllers\StateTransitionController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\LearningShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/home', [LearningShellController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::get('/labs/state-transition', [StateTransitionController::class, 'index'])
    ->middleware(['auth', 'lab.available'])
    ->defaults('labKey', 'state-transition')
    ->name('labs.state-transition.index');
Route::post('/labs/state-transition/tickets', [StateTransitionController::class, 'store'])
    ->middleware(['auth', 'lab.available'])
    ->defaults('labKey', 'state-transition')
    ->name('labs.state-transition.store');
Route::patch('/labs/state-transition/tickets/{ticket}/transition', [StateTransitionController::class, 'changeStatus'])
    ->middleware(['auth', 'lab.available'])
    ->defaults('labKey', 'state-transition')
    ->name('labs.state-transition.transition');

Route::get('/catalog/labs/{labKey}', [LabController::class, 'show'])
    ->middleware(['auth', 'lab.available'])
    ->name('labs.show');

Route::middleware(['auth', 'can:manage-catalog'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::patch('/catalog', [CatalogController::class, 'update'])->name('catalog.update');
        Route::patch('/catalog/labs/{labKey}', [CatalogController::class, 'updateLab'])->name('catalog.labs.update');
    });

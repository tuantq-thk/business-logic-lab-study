<?php

use App\Http\Controllers\LearningShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/home', [LearningShellController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

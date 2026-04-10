<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::post('/projects/{project}/retry', [ProjectController::class, 'retry'])->name('projects.retry');
Route::post('/projects/{project}/cancel', [ProjectController::class, 'cancel'])->name('projects.cancel');
Route::get('/projects/{project}/status', [ProjectController::class, 'status'])->name('projects.status');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::post('/projects/{project}/retry', [ProjectController::class, 'retry'])->name('projects.retry');
Route::post('/projects/{project}/regenerate', [ProjectController::class, 'regenerate'])->name('projects.regenerate');
Route::post('/projects/{project}/cancel', [ProjectController::class, 'cancel'])->name('projects.cancel');
Route::get('/projects/{project}/status', [ProjectController::class, 'status'])->name('projects.status');
Route::get('/projects/{project}/browse', [ProjectController::class, 'browse'])->name('projects.browse');
Route::get('/projects/{project}/browse/{path}', [ProjectController::class, 'browse'])->name('projects.browse.path')->where('path', '.*');
Route::get('/projects/{project}/view-file', [ProjectController::class, 'viewFile'])->name('projects.view');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

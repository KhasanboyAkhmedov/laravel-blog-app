<?php

use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('posts.index');
});

// All authenticated users: view posts
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => redirect()->route('posts.index'))->name('dashboard');

    // Posts — backend gates enforce per-action permissions
    Route::resource('posts', PostController::class);

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Superadmin-only
    Route::middleware(['role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    });
});

require __DIR__.'/auth.php';

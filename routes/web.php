<?php

use App\Http\Controllers\AccessProfileController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiayResourceController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::resource('cases', CaseController::class)->except(['destroy'])
        ->middleware('permission:cases.view_all,cases.view_municipality,cases.view_orgao,cases.view_assigned');

    Route::get('/perfis', [AccessProfileController::class, 'index'])
        ->middleware('permission:profiles.manage')
        ->name('profiles.index');
    Route::put('/perfis/{profile}', [AccessProfileController::class, 'update'])
        ->middleware('permission:profiles.manage')
        ->name('profiles.update');

    Route::resource('usuarios', UserManagementController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('permission:users.manage,users.manage_municipality')
        ->parameters(['usuarios' => 'user'])
        ->names('users');

    Route::get('/auditoria', AuditLogController::class)
        ->middleware('permission:audit.view')
        ->name('audit.index');

    Route::get('/modulos/{module}', [SiayResourceController::class, 'index'])->name('resources.index');
    Route::post('/modulos/{module}', [SiayResourceController::class, 'store'])->name('resources.store');
    Route::put('/modulos/{module}/{record}', [SiayResourceController::class, 'update'])->name('resources.update');
    Route::delete('/modulos/{module}/{record}', [SiayResourceController::class, 'destroy'])->name('resources.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

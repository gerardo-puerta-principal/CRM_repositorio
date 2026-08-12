<?php

use App\Http\Controllers\Installer\InstallerController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadReminderController;
use App\Http\Controllers\LeadImportController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ProtectInstallerAccess;
use App\Http\Middleware\RedirectIfInstalled;
use App\Http\Middleware\RedirectToInstaller;
use App\Services\Installer\InstallationStatus;
use Illuminate\Support\Facades\Route;

Route::get('/', function (InstallationStatus $installationStatus) {
    if ($installationStatus->isInstalled()) {
        return redirect()->route('login');
    }

    return redirect()->route('install.show', array_filter([
        'installer_key' => (string) request()->query('installer_key', ''),
    ]));
})->name('home');

Route::middleware([RedirectIfInstalled::class, ProtectInstallerAccess::class])->group(function (): void {
    Route::get('/install', [InstallerController::class, 'create'])->name('install.show');
    Route::post('/install', [InstallerController::class, 'store'])->name('install.store');
});

Route::middleware(RedirectToInstaller::class)->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AuthController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'active'])->group(function (): void {
        Route::redirect('/dashboard', '/leads')->name('dashboard');
        Route::post('/impersonation/leave', [UserController::class, 'leaveImpersonation'])->name('impersonation.leave');

        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
        Route::post('/leads/{lead}/interactions', [LeadController::class, 'storeInteraction'])->name('leads.interactions.store');
        Route::post('/leads/{lead}/reminders', [LeadReminderController::class, 'store'])->name('leads.reminders.store');
        Route::post('/reminders/{reminder}/complete', [LeadReminderController::class, 'complete'])->name('leads.reminders.complete');
        Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
        Route::post('/leads/round-robin', [LeadController::class, 'roundRobin'])->name('leads.round-robin');

        Route::get('/imports/leads', [LeadImportController::class, 'create'])->name('leads.import.create');
        Route::post('/imports/leads/preview', [LeadImportController::class, 'preview'])->name('leads.import.preview');
        Route::post('/imports/leads/confirm', [LeadImportController::class, 'store'])->name('leads.import.store');

        Route::middleware('role:super_admin,supervisor')->group(function (): void {
            Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics.index');
        });

        Route::middleware('role:super_admin')->group(function (): void {
            Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
        });
    });
});

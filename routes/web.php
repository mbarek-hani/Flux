<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TrackerJsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/tracker.js', TrackerJsController::class);

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name(
        'dashboard',
    );
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name(
        'dashboard.stats',
    );
    Route::post('/dashboard/reglages', [
        DashboardController::class,
        'sauvegarderReglages',
    ])->name('dashboard.reglages');

    Route::resource('sites', SiteController::class);
    Route::post('/sites/{site}/toggle-actif', [
        SiteController::class,
        'toggleActif',
    ])->name('sites.toggle-actif');

    Route::get('/plugins', [PluginController::class, 'index'])->name(
        'plugins.index',
    );
    Route::get('/plugins/{identifiant}', [PluginController::class, 'show'])->name(
        'plugins.show',
    );
    Route::post('/plugins/{identifiant}/activer', [
        PluginController::class,
        'activer',
    ])->name('plugins.activer');
    Route::post('/plugins/{identifiant}/desactiver', [
        PluginController::class,
        'desactiver',
    ])->name('plugins.desactiver');

    Route::get('/marketplace', [MarketplaceController::class, 'index'])->name(
        'marketplace.index',
    );
    Route::get('/marketplace/install/{id}/stream', [MarketplaceController::class, 'streamInstall'])->name(
        'marketplace.install.stream',
    );
    Route::get('/marketplace/update/{id}/stream', [MarketplaceController::class, 'streamUpdate'])->name(
        'marketplace.update.stream',
    );
    Route::get('/marketplace/uninstall/{id}/stream', [MarketplaceController::class, 'streamUninstall'])->name(
        'marketplace.uninstall.stream',
    );

    Route::get('/settings', [SettingsController::class, 'index'])->name(
        'settings.index',
    );
    Route::post('/settings/reglages', [
        SettingsController::class,
        'sauvegarderReglages',
    ])->name('settings.reglages');

    Route::get('/profile', [ProfileController::class, 'edit'])->name(
        'profile.edit',
    );
    Route::patch('/profile', [ProfileController::class, 'update'])->name(
        'profile.update',
    );
});

require __DIR__.'/auth.php';

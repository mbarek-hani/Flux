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

    Route::resource('sites', SiteController::class);
    Route::post('/sites/{site}/toggle-active', [
        SiteController::class,
        'toggleActive',
    ])->name('sites.toggle-active');

    Route::get('/plugins', [PluginController::class, 'index'])->name(
        'plugins.index',
    );
    Route::get('/plugins/{slug}', [PluginController::class, 'show'])->name(
        'plugins.show',
    );
    Route::post('/plugins/{slug}/enable', [
        PluginController::class,
        'enable',
    ])->name('plugins.enable');
    Route::post('/plugins/{slug}/disable', [
        PluginController::class,
        'disable',
    ])->name('plugins.disable');

    Route::get('/marketplace', [MarketplaceController::class, 'index'])->name(
        'marketplace.index',
    );
    Route::get('/marketplace/{id}', [MarketplaceController::class, 'show'])->name(
        'marketplace.show',
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
    Route::post('/settings', [
        SettingsController::class,
        'update',
    ])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name(
        'profile.edit',
    );
    Route::patch('/profile', [ProfileController::class, 'update'])->name(
        'profile.update',
    );
});

require __DIR__.'/auth.php';

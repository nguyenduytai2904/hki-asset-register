<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureHasDefaultAccount;
use App\Http\Middleware\ResolveCurrentAccount;
use Hki\AssetRegister\Asset\Http\Controllers\AssetRegisterController;
use Hki\AssetRegister\AssetRegisterModule;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    EnsureHasDefaultAccount::class,
    ResolveCurrentAccount::class,
    'account.selected',
    'subscription.active',
    'plugin.active:' . AssetRegisterModule::CODE,
])
    ->prefix('asset-register')
    ->name('asset-register.')
    ->group(function (): void {
        Route::get('/', [AssetRegisterController::class, 'index'])->name('index');
        Route::get('/mine', [AssetRegisterController::class, 'mine'])->name('mine');
        Route::get('/settings', [AssetRegisterController::class, 'settings'])->name('settings');

        Route::post('/settings/asset-types', [AssetRegisterController::class, 'storeType'])->name('settings.asset-types.store');
        Route::put('/settings/asset-types/{typeId}', [AssetRegisterController::class, 'updateType'])->name('settings.asset-types.update');
        Route::delete('/settings/asset-types/{typeId}', [AssetRegisterController::class, 'deleteType'])->name('settings.asset-types.delete');

        Route::post('/settings/locations', [AssetRegisterController::class, 'storeLocation'])->name('settings.locations.store');
        Route::put('/settings/locations/{locationId}', [AssetRegisterController::class, 'updateLocation'])->name('settings.locations.update');
        Route::delete('/settings/locations/{locationId}', [AssetRegisterController::class, 'deleteLocation'])->name('settings.locations.delete');
    });

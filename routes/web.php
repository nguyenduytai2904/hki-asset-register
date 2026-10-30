<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureHasDefaultAccount;
use App\Http\Middleware\ResolveCurrentAccount;
use Hki\AssetRegister\Asset\Http\Controllers\AssetController;
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
        Route::get('/registry', [AssetController::class, 'registry'])->name('registry');
        Route::get('/registry/create', [AssetController::class, 'create'])->name('assets.create');
        Route::post('/registry', [AssetController::class, 'store'])->name('assets.store');
        Route::get('/assets/{assetId}', [AssetController::class, 'show'])->whereNumber('assetId')->name('assets.show');
        Route::get('/assets/{assetId}/edit', [AssetController::class, 'edit'])->whereNumber('assetId')->name('assets.edit');
        Route::put('/assets/{assetId}', [AssetController::class, 'update'])->whereNumber('assetId')->name('assets.update');
        Route::post('/assets/{assetId}/status', [AssetController::class, 'changeStatus'])->whereNumber('assetId')->name('assets.status');
        Route::post('/assets/{assetId}/dispose', [AssetController::class, 'dispose'])->whereNumber('assetId')->name('assets.dispose');
        Route::get('/settings', [AssetRegisterController::class, 'settings'])->name('settings');

        Route::post('/settings/asset-types', [AssetRegisterController::class, 'storeType'])->name('settings.asset-types.store');
        Route::put('/settings/asset-types/{typeId}', [AssetRegisterController::class, 'updateType'])->name('settings.asset-types.update');
        Route::delete('/settings/asset-types/{typeId}', [AssetRegisterController::class, 'deleteType'])->name('settings.asset-types.delete');

        Route::post('/settings/locations', [AssetRegisterController::class, 'storeLocation'])->name('settings.locations.store');
        Route::put('/settings/locations/{locationId}', [AssetRegisterController::class, 'updateLocation'])->name('settings.locations.update');
        Route::delete('/settings/locations/{locationId}', [AssetRegisterController::class, 'deleteLocation'])->name('settings.locations.delete');
    });

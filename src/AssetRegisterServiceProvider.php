<?php

declare(strict_types=1);

namespace Hki\AssetRegister;

use App\Support\Plugin\BaseModule;
use App\Support\Plugin\BasePluginServiceProvider;
use Hki\AssetRegister\Asset\Contracts\Repositories\AssetRepositoryInterface;
use Hki\AssetRegister\Asset\Contracts\Services\AssetServiceInterface;
use Hki\AssetRegister\Asset\Repositories\AssetRepository;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Policies\AssetPolicy;
use Hki\AssetRegister\Asset\Services\AssetService;
use Hki\AssetRegister\Rbac\AssetRegisterPermissionProvider;
use App\Support\Rbac\AccountPermissionCatalogRegistry;
use Hki\AssetRegister\Console\Commands\AssetRegisterDemoData;
use Illuminate\Support\Facades\Gate;

final class AssetRegisterServiceProvider extends BasePluginServiceProvider
{
    public function module(): BaseModule
    {
        return new AssetRegisterModule();
    }

    protected function registerPluginBindings(): void
    {
        $this->app->bind(AssetRepositoryInterface::class, AssetRepository::class);
        $this->app->bind(AssetServiceInterface::class, AssetService::class);
    }

    protected function bootPlugin(): void
    {
        Gate::policy(Asset::class, AssetPolicy::class);
        $this->commands([AssetRegisterDemoData::class]);
        $this->callAfterResolving(AccountPermissionCatalogRegistry::class, function (AccountPermissionCatalogRegistry $registry): void {
            $registry->register(new AssetRegisterPermissionProvider());
        });
    }
}

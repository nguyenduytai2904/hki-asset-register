<?php

declare(strict_types=1);

namespace Hki\AssetRegister;

use App\Support\Plugin\BaseModule;
use App\Support\Plugin\BasePluginServiceProvider;
use Hki\AssetRegister\Rbac\AssetRegisterPermissionProvider;
use App\Support\Rbac\AccountPermissionCatalogRegistry;
use Hki\AssetRegister\Console\Commands\AssetRegisterDemoData;

final class AssetRegisterServiceProvider extends BasePluginServiceProvider
{
    public function module(): BaseModule
    {
        return new AssetRegisterModule();
    }

    protected function registerPluginBindings(): void
    {
    }

    protected function bootPlugin(): void
    {
        $this->commands([AssetRegisterDemoData::class]);
        $this->callAfterResolving(AccountPermissionCatalogRegistry::class, function (AccountPermissionCatalogRegistry $registry): void {
            $registry->register(new AssetRegisterPermissionProvider());
        });
    }
}

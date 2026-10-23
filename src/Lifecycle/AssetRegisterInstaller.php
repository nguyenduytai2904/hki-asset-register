<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Lifecycle;

use App\Support\Plugin\Contracts\PluginInstaller;
use App\Support\Plugin\DTOs\InstallResult;
use Hki\AssetRegister\AssetRegisterModule;
use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;

final class AssetRegisterInstaller implements PluginInstaller
{
    public function install(): InstallResult
    {
        $created = AssetRegisterSchema::createMissing();

        return new InstallResult(
            true,
            AssetRegisterModule::CODE,
            tablesCreated: $created,
        );
    }
}

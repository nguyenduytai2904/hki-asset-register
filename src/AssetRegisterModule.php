<?php

declare(strict_types=1);

namespace Hki\AssetRegister;

use App\Support\Plugin\BaseModule;
use App\Support\Plugin\DTOs\DefaultRoleConfig;
use App\Support\Plugin\DTOs\SidebarConfig;
use Composer\InstalledVersions;
use Hki\AssetRegister\Lifecycle\AssetRegisterAccountGrant;
use Hki\AssetRegister\Lifecycle\AssetRegisterAccountRevoke;
use Hki\AssetRegister\Lifecycle\AssetRegisterInstaller;
use Hki\AssetRegister\Lifecycle\AssetRegisterPurger;
use Hki\AssetRegister\Lifecycle\AssetRegisterUninstaller;
use Hki\AssetRegister\Rbac\AssetRegisterPermissions;
use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;

final class AssetRegisterModule extends BaseModule
{
    public const CODE = 'asset-register';

    public const TABLES = AssetRegisterSchema::TABLE_ORDER;

    public function code(): string
    {
        return self::CODE;
    }

    public function name(): array
    {
        return ['vi' => 'Tài sản', 'en' => 'Assets'];
    }

    public function version(): string
    {
        if (InstalledVersions::isInstalled('hki/asset-register')) {
            $version = InstalledVersions::getPrettyVersion('hki/asset-register');
            if (is_string($version) && $version !== '') {
                return ltrim($version, 'v');
            }
        }

        return '0.0.0-dev';
    }

    public function icon(): string
    {
        return 'inventory_2';
    }

    public function author(): array
    {
        return ['name' => 'HKI'];
    }

    public function coreVersionConstraint(): string
    {
        return '^1.2';
    }

    public function description(): array
    {
        return [
            'vi' => 'Plugin quản lý vòng đời tài sản thiết bị của tổ chức.',
            'en' => 'Asset lifecycle management for an organization.',
        ];
    }

    public function ownsTables(): array
    {
        return self::TABLES;
    }

    public function ownsPermissions(): array
    {
        return AssetRegisterPermissions::all();
    }

    public function sidebar(): ?SidebarConfig
    {
        return new SidebarConfig(
            slot: 'main',
            order: 80,
            icon: $this->icon(),
            routeName: 'asset-register.index',
            labelKey: 'asset-register::asset-register.name',
        );
    }

    public function installerClass(): string
    {
        return AssetRegisterInstaller::class;
    }

    public function accountGrantClass(): string
    {
        return AssetRegisterAccountGrant::class;
    }

    public function accountRevokeClass(): string
    {
        return AssetRegisterAccountRevoke::class;
    }

    public function uninstallerClass(): string
    {
        return AssetRegisterUninstaller::class;
    }

    public function purgerClass(): string
    {
        return AssetRegisterPurger::class;
    }

    public function defaultRoles(): array
    {
        return [
            new DefaultRoleConfig(
                code: 'asset-register.asset_manager',
                name: ['vi' => 'Quản lý sổ tài sản', 'en' => 'Asset Register Manager'],
                permissions: AssetRegisterPermissions::all(),
                isDefaultForAccountOwner: true,
            ),
        ];
    }
}

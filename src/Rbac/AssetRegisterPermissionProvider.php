<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Rbac;

use App\Contracts\Rbac\AccountPermissionProviderContract;
use App\Support\Rbac\PluginPermissionDef;
use Hki\AssetRegister\AssetRegisterModule;

/**
 * Permission catalog của plugin Asset Register.
 *
 * ownsPermissions() giúp plugin:sync biết ownership; provider này giúp core RBAC
 * hiển thị quyền trong màn hình quản lý role và cấp quyền cho role full-access.
 */
final class AssetRegisterPermissionProvider implements AccountPermissionProviderContract
{
    public function providerKey(): string
    {
        return AssetRegisterModule::CODE.'.assets';
    }

    public function ownerPluginCode(): string
    {
        return AssetRegisterModule::CODE;
    }

    public function groupLabel(): string
    {
        return __('asset-register::asset-register.permissions.group');
    }

    public function permissions(): array
    {
        return [
            new PluginPermissionDef(
                key: AssetRegisterPermissions::SELF_USE,
                label: __('asset-register::asset-register.permissions.self_use.label'),
                description: __('asset-register::asset-register.permissions.self_use.description'),
                icon: 'person',
            ),
            new PluginPermissionDef(
                key: AssetRegisterPermissions::ASSETS_VIEW,
                label: __('asset-register::asset-register.permissions.assets_view.label'),
                description: __('asset-register::asset-register.permissions.assets_view.description'),
                icon: 'visibility',
            ),
            new PluginPermissionDef(
                key: AssetRegisterPermissions::ASSETS_MANAGE,
                label: __('asset-register::asset-register.permissions.assets_manage.label'),
                description: __('asset-register::asset-register.permissions.assets_manage.description'),
                icon: 'inventory',
            ),
        ];
    }
}

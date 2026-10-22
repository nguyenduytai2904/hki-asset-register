<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Rbac;

use App\Contracts\Rbac\AccountPermissionProviderContract;
use App\Support\Rbac\PluginPermissionDef;
use Hki\AssetRegister\AssetRegisterModule;
use Hki\AssetRegister\Rbac\AssetRegisterPermissions;

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
        return AssetRegisterModule::CODE . '.assets';
    }

    public function ownerPluginCode(): string
    {
        return AssetRegisterModule::CODE;
    }

    public function groupLabel(): string
    {
        return 'Tài sản';
    }

    public function permissions(): array
    {
        return [
            new PluginPermissionDef(
                key: AssetRegisterPermissions::SELF_USE,
                label: 'Tự phục vụ',
                description: 'Sử dụng tài sản được cấp cho chính mình.',
                icon: 'person',
            ),
            new PluginPermissionDef(
                key: AssetRegisterPermissions::ASSETS_VIEW,
                label: 'Xem sổ tài sản',
                description: 'Xem danh sách tài sản thiết bị của account.',
                icon: 'visibility',
            ),
            new PluginPermissionDef(
                key: AssetRegisterPermissions::ASSETS_MANAGE,
                label: 'Quản lý sổ tài sản',
                description: 'Tạo, cập nhật và xóa tài sản trong account.',
                icon: 'inventory',
            ),
        ];
    }
}

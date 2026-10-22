<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Rbac;

final class AssetRegisterPermissions
{
    public const SELF_USE = 'asset-register.self.use';
    public const ASSETS_VIEW = 'asset-register.assets.view';
    public const ASSETS_MANAGE = 'asset-register.assets.manage';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SELF_USE,
            self::ASSETS_VIEW,
            self::ASSETS_MANAGE,
        ];
    }
}
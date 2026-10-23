<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Architecture;

use Hki\AssetRegister\AssetRegisterModule;
use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;
use PHPUnit\Framework\TestCase;

final class AssetRegisterStructureTest extends TestCase
{
    public function test_manifest_uses_stable_plugin_code(): void
    {
        self::assertSame('asset-register', (new AssetRegisterModule)->code());
    }

    public function test_manifest_declares_only_prefixed_plugin_tables(): void
    {
        $tables = (new AssetRegisterModule)->ownsTables();

        self::assertSame(AssetRegisterSchema::TABLE_ORDER, $tables);
        foreach ($tables as $table) {
            self::assertStringStartsWith('asset_register_', $table);
        }
    }
}

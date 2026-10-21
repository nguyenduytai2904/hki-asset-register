<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Architecture;

use Hki\AssetRegister\AssetRegisterModule;
use PHPUnit\Framework\TestCase;

final class AssetRegisterStructureTest extends TestCase
{
    public function test_manifest_uses_stable_plugin_code(): void
    {
        self::assertSame('asset-register', (new AssetRegisterModule())->code());
    }
}

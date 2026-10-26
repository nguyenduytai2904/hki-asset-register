<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Unit;

use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetHandover;
use Hki\AssetRegister\Asset\Models\AssetHistory;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;
use PHPUnit\Framework\TestCase;

final class AssetAccountScopeTest extends TestCase
{
    public function test_every_account_owned_asset_model_uses_the_core_account_scope(): void
    {
        foreach ([Asset::class, AssetHandover::class, AssetHistory::class, AssetLocation::class, AssetType::class] as $model) {
            self::assertContains(
                \App\Support\Account\Concerns\BelongsToAccount::class,
                class_uses_recursive($model),
            );
        }
    }
}

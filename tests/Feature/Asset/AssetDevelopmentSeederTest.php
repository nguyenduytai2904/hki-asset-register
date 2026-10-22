<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

use Hki\AssetRegister\Database\Seeders\AssetRegisterDevelopmentSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AssetDevelopmentSeederTest extends TestCase
{
    public function test_development_fixture_creates_nine_types_five_locations_and_eighteen_assets(): void
    {
        (new AssetRegisterDevelopmentSeeder)->run();

        $accountId = DB::table('accounts')
            ->where('name', 'Asset Register Test Workspace')
            ->value('id');

        self::assertNotNull($accountId);
        self::assertSame(9, DB::table('asset_types')->where('account_id', $accountId)->count());
        self::assertSame(5, DB::table('asset_locations')->where('account_id', $accountId)->count());
        self::assertSame(18, DB::table('assets')->where('account_id', $accountId)->count());
    }
}

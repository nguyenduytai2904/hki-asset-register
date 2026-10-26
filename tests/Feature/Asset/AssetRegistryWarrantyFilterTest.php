<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

require_once dirname(__DIR__, 2).'/Support/InteractsWithAssetRegister.php';

use App\Support\Account\AccountContext;
use App\Support\Timezone\TimezoneContext;
use Carbon\Carbon;
use Hki\AssetRegister\Asset\Contracts\Repositories\AssetRepositoryInterface;
use Hki\AssetRegister\Asset\DTOs\AssetRegistryFilters;
use Hki\AssetRegister\Tests\Support\InteractsWithAssetRegister;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AssetRegistryWarrantyFilterTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_warranty_filters_exclude_disposed_assets(): void
    {
        [, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        AccountContext::force((int) $account->id);

        $typeId = DB::table('asset_register_types')->insertGetId([
            'account_id' => $account->id,
            'name' => 'Laptop',
            'prefix' => 'LT',
            'status' => 'active',
            'next_code_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $upcomingIds = [
            $this->createAsset((int) $account->id, $typeId, 'LT-0001', 'available', now()->addDays(10)->toDateString()),
            $this->createAsset((int) $account->id, $typeId, 'LT-0002', 'disposed', now()->addDays(10)->toDateString()),
        ];
        $expiredIds = [
            $this->createAsset((int) $account->id, $typeId, 'LT-0003', 'available', now()->subDay()->toDateString()),
            $this->createAsset((int) $account->id, $typeId, 'LT-0004', 'disposed', now()->subDay()->toDateString()),
        ];

        $repository = app(AssetRepositoryInterface::class);

        self::assertSame(
            [$upcomingIds[0]],
            $repository->paginateRegistry((int) $account->id, $this->filters(['expiring']))
                ->getCollection()
                ->modelKeys(),
        );
        self::assertSame(
            [$expiredIds[0]],
            $repository->paginateRegistry((int) $account->id, $this->filters(['expired']))
                ->getCollection()
                ->modelKeys(),
        );
    }

    public function test_warranty_filter_uses_the_viewers_timezone_date(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        AccountContext::force((int) $account->id);
        $this->actingAs($actor);

        $typeId = DB::table('asset_register_types')->insertGetId([
            'account_id' => $account->id,
            'name' => 'Laptop',
            'prefix' => 'LT',
            'status' => 'active',
            'next_code_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow('2026-10-23 16:30:00 UTC');
        TimezoneContext::force('Pacific/Kiritimati');

        try {
            $localYesterday = $this->createAsset((int) $account->id, $typeId, 'LT-0001', 'available', '2026-10-23');
            $localToday = $this->createAsset((int) $account->id, $typeId, 'LT-0002', 'available', '2026-10-24');
            $localTomorrow = $this->createAsset((int) $account->id, $typeId, 'LT-0003', 'available', '2026-10-25');
            $disposedToday = $this->createAsset((int) $account->id, $typeId, 'LT-0004', 'disposed', '2026-10-24');
            $repository = app(AssetRepositoryInterface::class);

            self::assertSame(
                [$localYesterday],
                $repository->paginateRegistry((int) $account->id, $this->filters(['expired']))
                    ->getCollection()
                    ->modelKeys(),
            );
            self::assertSame(
                [$localTomorrow],
                $repository->paginateRegistry((int) $account->id, $this->filters(['expiring']))
                    ->getCollection()
                    ->modelKeys(),
            );
            $this->actingAs($actor)
                ->get(route('asset-register.assets.show', $localToday))
                ->assertOk()
                ->assertSee(__('asset-register::asset-register.registry.warranty_today'))
                ->assertDontSee(__('asset-register::asset-register.registry.expired'));
            $this->actingAs($actor)
                ->get(route('asset-register.assets.show', $disposedToday))
                ->assertOk()
                ->assertDontSee(__('asset-register::asset-register.registry.warranty_today'))
                ->assertDontSee(__('asset-register::asset-register.registry.expired'));
            $registry = $this->actingAs($actor)->get(route('asset-register.registry'))->assertOk();
            self::assertSame(
                1,
                substr_count((string) $registry->getContent(), __('asset-register::asset-register.registry.warranty_today')),
            );
        } finally {
            TimezoneContext::clear();
            Carbon::setTestNow();
        }
    }

    private function createAsset(int $accountId, int $typeId, string $code, string $status, string $warrantyEndDate): int
    {
        return DB::table('asset_register_assets')->insertGetId([
            'account_id' => $accountId,
            'asset_type_id' => $typeId,
            'code' => $code,
            'name' => $code,
            'condition' => 'good',
            'status' => $status,
            'warranty_end_date' => $warrantyEndDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param list<string> $warranties */
    private function filters(array $warranties): AssetRegistryFilters
    {
        return new AssetRegistryFilters(
            search: '',
            statuses: [],
            typeIds: [],
            locationIds: [],
            warranties: $warranties,
            sort: 'code',
            direction: 'asc',
            perPage: 15,
            page: 1,
            hasExplicitSort: false,
        );
    }
}

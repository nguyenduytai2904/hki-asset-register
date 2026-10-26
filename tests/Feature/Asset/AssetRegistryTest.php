<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

require_once dirname(__DIR__, 2).'/Support/InteractsWithAssetRegister.php';

use App\Contracts\Services\NotificationServices\NotificationAutoReadServiceInterface;
use App\Models\User;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Tests\Support\InteractsWithAssetRegister;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

final class AssetRegistryTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_registry_defaults_to_fifteen_rows_and_clamps_out_of_range_pages(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        $typeId = $this->insertType((int) $account->id);

        for ($index = 1; $index <= 16; $index++) {
            $this->insertAsset((int) $account->id, $typeId, sprintf('LT-%04d', $index));
        }

        $response = $this->actingAs($actor)
            ->get(route('asset-register.registry', ['page' => 99]));

        $response->assertOk()
            ->assertSee('data-hk-current-page="2"', false);
        self::assertSame(15, $response->viewData('assets')->perPage());
        self::assertSame(2, $response->viewData('assets')->currentPage());
        self::assertCount(1, $response->viewData('assets'));
    }

    public function test_registry_search_and_filters_return_only_matching_assets(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        $typeId = $this->insertType((int) $account->id);
        $locationId = $this->insertLocation((int) $account->id);
        $matchingAssetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0002', 'available', $locationId);
        $excludedAssetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'broken', $locationId);
        DB::table('asset_register_assets')->where('id', $matchingAssetId)->update([
            'name' => 'Work laptop',
            'serial' => 'SERIAL-42',
        ]);
        DB::table('asset_register_assets')->where('id', $excludedAssetId)->update([
            'name' => 'Work laptop',
            'serial' => 'SERIAL-43',
        ]);

        $response = $this->actingAs($actor)->get(route('asset-register.registry', [
            'q' => 'SERIAL-4',
            'status' => ['available', 'broken'],
            'type' => [$typeId],
            'location' => [$locationId],
        ]));

        $response->assertOk();
        self::assertSame(
            [$excludedAssetId, $matchingAssetId],
            $response->viewData('assets')->getCollection()->modelKeys(),
        );
    }

    public function test_in_use_status_filter_includes_assets_waiting_for_handover_confirmation(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        $typeId = $this->insertType((int) $account->id);
        $recipient = User::factory()->create();
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'in_use', null, (int) $recipient->id);

        DB::table('asset_register_handovers')->insert([
            'account_id' => $account->id,
            'asset_id' => $assetId,
            'recipient_id' => $recipient->id,
            'status' => 'pending',
            'handed_over_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($actor)->get(route('asset-register.registry', [
            'status' => ['in_use'],
        ]));

        self::assertSame(
            [$assetId],
            $response->viewData('assets')->getCollection()->modelKeys(),
        );
    }

    public function test_generated_asset_codes_are_sequential_and_never_reused(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'disposed');

        $generated = $this->actingAs($actor)->post(route('asset-register.assets.store'), $this->assetPayload($typeId));
        $generated->assertRedirect();
        $this->assertDatabaseHas('asset_register_assets', [
            'account_id' => $account->id,
            'code' => 'LT-0002',
            'status' => 'available',
            'current_keeper_id' => null,
        ]);

        $manualPayload = $this->assetPayload($typeId);
        $manualPayload['code'] = ' old-tag-9 ';
        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), $manualPayload)
            ->assertRedirect();
        $this->assertDatabaseHas('asset_register_assets', ['account_id' => $account->id, 'code' => 'OLD-TAG-9']);
        self::assertSame(3, (int) DB::table('asset_register_types')->where('id', $typeId)->value('next_code_number'));

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), $this->assetPayload($typeId))
            ->assertRedirect();
        $this->assertDatabaseHas('asset_register_assets', ['account_id' => $account->id, 'code' => 'LT-0003']);
        self::assertSame(3, DB::table('asset_register_histories')
            ->where('account_id', $account->id)
            ->where('action', 'added')
            ->count());
    }

    public function test_asset_creation_rejects_invalid_codes_inactive_catalogs_and_warranty_dates(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $activeTypeId = $this->insertType((int) $account->id);
        $inactiveTypeId = $this->insertType((int) $account->id, 'inactive', 'Monitor', 'MN');
        $inactiveLocationId = $this->insertLocation((int) $account->id, 'inactive');

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), array_replace($this->assetPayload($inactiveTypeId), [
                'asset_location_id' => $inactiveLocationId,
            ]))
            ->assertSessionHasErrors(['asset_type_id', 'asset_location_id']);

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), array_replace($this->assetPayload($activeTypeId), [
                'code' => 'bad code',
                'purchase_date' => '2026-10-20',
                'warranty_end_date' => '2026-10-19',
            ]))
            ->assertSessionHasErrors(['code', 'warranty_end_date']);
    }

    public function test_asset_create_and_edit_forms_warn_against_personal_data_in_notes(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001');
        $privacyHint = __('asset-register::asset-register.actions.no_personal_data');

        $this->actingAs($actor)
            ->get(route('asset-register.assets.create'))
            ->assertOk()
            ->assertSee($privacyHint);

        $this->actingAs($actor)
            ->get(route('asset-register.assets.edit', $assetId))
            ->assertOk()
            ->assertSee($privacyHint);
    }

    public function test_asset_update_preserves_code_logs_changed_fields_and_keeps_existing_inactive_catalog_values(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id, 'inactive');
        $locationId = $this->insertLocation((int) $account->id, 'inactive');
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'available', $locationId);

        $response = $this->actingAs($actor)->put(
            route('asset-register.assets.update', $assetId),
            array_replace($this->assetPayload($typeId, $locationId), ['name' => 'Updated laptop', 'code' => 'CHANGED']),
        );

        $response->assertRedirect(route('asset-register.assets.show', $assetId));
        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'code' => 'LT-0001',
            'name' => 'Updated laptop',
            'asset_type_id' => $typeId,
            'asset_location_id' => $locationId,
        ]);

        $history = DB::table('asset_register_histories')
            ->where('asset_id', $assetId)
            ->where('action', 'updated')
            ->sole();
        self::assertSame([
            'name' => ['old' => 'LT-0001', 'new' => 'Updated laptop'],
        ], json_decode($history->description, true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_asset_update_rejects_switching_to_an_inactive_catalog_value(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $activeTypeId = $this->insertType((int) $account->id);
        $inactiveTypeId = $this->insertType((int) $account->id, 'inactive', 'Monitor', 'MN');
        $assetId = $this->insertAsset((int) $account->id, $activeTypeId, 'LT-0001');

        $this->actingAs($actor)
            ->put(
                route('asset-register.assets.update', $assetId),
                array_replace($this->assetPayload($inactiveTypeId), ['name' => 'Updated laptop']),
            )
            ->assertSessionHasErrors('asset_type_id');
    }

    public function test_disposed_asset_cannot_be_updated(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'disposed');

        $this->actingAs($actor)
            ->get(route('asset-register.assets.edit', $assetId))
            ->assertForbidden();
        $this->actingAs($actor)
            ->put(route('asset-register.assets.update', $assetId), $this->assetPayload($typeId))
            ->assertForbidden();
    }

    public function test_authorized_asset_detail_marks_notifications_read(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001');

        $autoRead = Mockery::mock(NotificationAutoReadServiceInterface::class);
        $autoRead->shouldReceive('markSubjectsRead')
            ->once()
            ->with((int) $actor->id, Asset::NOTIFICATION_SUBJECT_TYPE, [$assetId])
            ->andReturn(1);
        $this->app->instance(NotificationAutoReadServiceInterface::class, $autoRead);

        $this->actingAs($actor)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertOk();
    }

    public function test_keeper_can_see_only_own_asset_history_and_handovers(): void
    {
        [$keeper, $account] = $this->actorWithPermissions(['asset-register.self.use']);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'in_use', null, (int) $keeper->id);
        $otherMember = User::factory()->create(['name' => 'Unrelated Recipient']);

        DB::table('asset_register_histories')->insert([
            [
                'account_id' => $account->id,
                'asset_id' => $assetId,
                'user_id' => $keeper->id,
                'action' => 'updated',
                'description' => 'KEEPER_VISIBLE_HISTORY',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_id' => $account->id,
                'asset_id' => $assetId,
                'user_id' => $otherMember->id,
                'action' => 'updated',
                'description' => 'PRIVATE_MANAGER_HISTORY',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
        ]);
        DB::table('asset_register_handovers')->insert([
            [
                'account_id' => $account->id,
                'asset_id' => $assetId,
                'recipient_id' => $keeper->id,
                'status' => 'confirmed',
                'handed_over_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_id' => $account->id,
                'asset_id' => $assetId,
                'recipient_id' => $otherMember->id,
                'status' => 'confirmed',
                'handed_over_at' => now()->subMinute(),
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
        ]);

        $response = $this->actingAs($keeper)->get(route('asset-register.assets.show', $assetId));

        $response->assertOk()
            ->assertSee('KEEPER_VISIBLE_HISTORY')
            ->assertDontSee('PRIVATE_MANAGER_HISTORY')
            ->assertDontSee('Unrelated Recipient')
            ->assertDontSee('Sổ tài sản');

        [$unrelated] = $this->addMemberWithPermissions($account, ['asset-register.self.use'], 'unrelated viewer');
        $autoRead = Mockery::mock(NotificationAutoReadServiceInterface::class);
        $autoRead->shouldNotReceive('markSubjectsRead');
        $this->app->instance(NotificationAutoReadServiceInterface::class, $autoRead);

        $this->actingAs($unrelated)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertForbidden();
    }

    public function test_asset_from_another_account_is_not_visible(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.assets.view']);
        [, $otherAccount] = $this->makeAccountWithFullAccessOwner('other asset account');
        $typeId = $this->insertType((int) $otherAccount->id);
        $assetId = $this->insertAsset((int) $otherAccount->id, $typeId, 'LT-0001');

        $this->actingAs($actor)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertNotFound();
    }

    private function insertType(int $accountId, string $status = 'active', string $name = 'Laptop', string $prefix = 'LT'): int
    {
        return DB::table('asset_register_types')->insertGetId([
            'account_id' => $accountId,
            'name' => $name,
            'prefix' => $prefix,
            'status' => $status,
            'next_code_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertLocation(int $accountId, string $status = 'active'): int
    {
        return DB::table('asset_register_locations')->insertGetId([
            'account_id' => $accountId,
            'name' => 'Server room',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertAsset(
        int $accountId,
        int $typeId,
        string $code,
        string $status = 'available',
        ?int $locationId = null,
        ?int $keeperId = null,
    ): int {
        return DB::table('asset_register_assets')->insertGetId([
            'account_id' => $accountId,
            'asset_type_id' => $typeId,
            'asset_location_id' => $locationId,
            'code' => $code,
            'name' => $code,
            'condition' => 'good',
            'status' => $status,
            'current_keeper_id' => $keeperId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assetPayload(int $typeId, ?int $locationId = null): array
    {
        return [
            'asset_type_id' => $typeId,
            'asset_location_id' => $locationId,
            'name' => 'LT-0001',
            'brand' => null,
            'model' => null,
            'serial' => null,
            'configuration' => null,
            'condition' => 'good',
            'purchase_date' => null,
            'warranty_end_date' => null,
            'supplier' => null,
            'receipt_number' => null,
            'note' => null,
        ];
    }
}

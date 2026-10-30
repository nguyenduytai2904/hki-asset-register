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

    public function test_registry_shows_the_latest_status_reason_on_desktop_only(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'in_repair');
        $assetWithoutStatusChangeId = $this->insertAsset((int) $account->id, $typeId, 'LT-0002');

        DB::table('asset_register_histories')->insert([
            [
                'account_id' => $account->id,
                'asset_id' => $assetId,
                'user_id' => $actor->id,
                'action' => 'status_changed',
                'description' => json_encode([
                    'from' => 'available',
                    'to' => 'in_repair',
                    'reason' => 'Older status reason',
                ], JSON_THROW_ON_ERROR),
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'account_id' => $account->id,
                'asset_id' => $assetId,
                'user_id' => $actor->id,
                'action' => 'status_changed',
                'description' => json_encode([
                    'from' => 'broken',
                    'to' => 'in_repair',
                    'reason' => 'Latest status reason',
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($actor)->get(route('asset-register.registry'));

        $response->assertOk()
            ->assertSee('data-ar-registry-status-change', false)
            ->assertSee('id="ar-registry-status-change-'.$assetId.'"', false)
            ->assertSee('Latest status reason')
            ->assertDontSee('Older status reason');

        $content = (string) $response->getContent();
        self::assertSame(1, substr_count($content, 'data-ar-registry-status-change'));
        preg_match('/<section class="ar-registry-cards".*?<\\/section>/s', $content, $mobileCards);
        self::assertArrayHasKey(0, $mobileCards);
        self::assertStringNotContainsString('data-ar-registry-status-change', $mobileCards[0]);
        self::assertStringNotContainsString('Latest status reason', $mobileCards[0]);
        self::assertStringNotContainsString(
            'id="ar-registry-status-change-'.$assetWithoutStatusChangeId.'"',
            $content,
        );
    }

    public function test_registry_sorts_status_and_warranty_and_accepts_page_size(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        $typeId = $this->insertType((int) $account->id);
        $inUseId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'in_use');
        $availableId = $this->insertAsset((int) $account->id, $typeId, 'LT-0002', 'available');
        $brokenId = $this->insertAsset((int) $account->id, $typeId, 'LT-0003', 'broken');
        DB::table('asset_register_assets')->where('id', $inUseId)->update(['warranty_end_date' => '2026-12-20']);
        DB::table('asset_register_assets')->where('id', $availableId)->update(['warranty_end_date' => '2026-11-20']);

        $statusResponse = $this->actingAs($actor)->get(route('asset-register.registry', [
            'sort_by' => 'status',
            'sort_dir' => 'asc',
            'per_page' => 30,
        ]));
        self::assertSame(
            [$availableId, $inUseId, $brokenId],
            $statusResponse->viewData('assets')->getCollection()->modelKeys(),
        );
        self::assertSame(30, $statusResponse->viewData('assets')->perPage());

        $warrantyResponse = $this->actingAs($actor)->get(route('asset-register.registry', [
            'sort_by' => 'warranty',
            'sort_dir' => 'asc',
            'per_page' => 30,
        ]));
        self::assertSame(
            [$availableId, $inUseId, $brokenId],
            $warrantyResponse->viewData('assets')->getCollection()->modelKeys(),
        );
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

    public function test_asset_creation_requires_business_fields_and_iso_date_values(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $payload = $this->assetPayload($typeId);
        unset($payload['asset_type_id'], $payload['name'], $payload['condition']);

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), $payload)
            ->assertSessionHasErrors(['asset_type_id', 'name', 'condition']);

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), array_replace($this->assetPayload($typeId), [
                'purchase_date' => '05/04/2025',
                'warranty_end_date' => '2026-04-05',
            ]))
            ->assertSessionHasErrors('purchase_date');
    }

    public function test_asset_creation_enforces_documented_text_field_limits(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), array_replace($this->assetPayload($typeId), [
                'name' => str_repeat('n', 151),
                'brand' => str_repeat('b', 101),
                'model' => str_repeat('m', 101),
                'serial' => str_repeat('s', 101),
                'configuration' => str_repeat('c', 2001),
                'supplier' => str_repeat('s', 151),
                'receipt_number' => str_repeat('r', 101),
                'note' => str_repeat('n', 2001),
            ]))
            ->assertSessionHasErrors([
                'name',
                'brand',
                'model',
                'serial',
                'configuration',
                'supplier',
                'receipt_number',
                'note',
            ]);
    }

    public function test_asset_creation_rejects_a_code_already_used_in_the_account(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $this->insertAsset((int) $account->id, $typeId, 'LT-0001');

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), array_replace($this->assetPayload($typeId), [
                'code' => 'lt-0001',
            ]))
            ->assertSessionHasErrors('code');
    }

    public function test_asset_create_dialog_and_edit_dialog_use_the_same_date_fields(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001');
        DB::table('asset_register_assets')->where('id', $assetId)->update([
            'purchase_date' => '2025-04-05',
            'warranty_end_date' => '2026-04-05',
            'warranty_duration_months' => 24,
        ]);

        $this->actingAs($actor)
            ->get(route('asset-register.registry'))
            ->assertOk()
            ->assertSee('id="ar-asset-form-dialog"', false)
            ->assertSee('class="ar-button ar-button-dark ar-add-asset"', false);

        $createForm = $this->actingAs($actor)
            ->get(route('asset-register.assets.create'))
            ->assertOk();
        $createForm->assertSee('name="purchase_date"', false)
            ->assertSee('name="warranty_end_date"', false)
            ->assertSee('name="warranty_duration_preset"', false)
            ->assertSee('name="warranty_duration_custom_months"', false)
            ->assertSee('data-hk-datepicker="date"', false);

        $detail = $this->actingAs($actor)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertOk();
        $detail->assertSee('class="ar-button ar-edit-asset"', false)
            ->assertSee('id="ar-asset-form-dialog"', false)
            ->assertSee('24 tháng');

        $this->actingAs($actor)
            ->get(route('asset-register.assets.edit', $assetId))
            ->assertOk()
            ->assertSee('value="2025-04-05"', false)
            ->assertSee('value="2026-04-05"', false)
            ->assertSee('value="24"', false)
            ->assertSee('name="warranty_duration_preset"', false)
            ->assertSee('value="LT-0001" readonly', false);
    }

    public function test_asset_creation_calculates_warranty_duration_from_purchase_date_and_allows_override(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);

        $this->actingAs($actor)->post(route('asset-register.assets.store'), array_replace(
            $this->assetPayload($typeId),
            [
                'code' => 'LT-0200',
                'purchase_date' => '2025-01-31',
                'warranty_duration_preset' => '3',
                'warranty_duration_months' => '',
                'warranty_end_date' => '',
            ],
        ))->assertRedirect();

        $this->assertDatabaseHas('asset_register_assets', [
            'account_id' => $account->id,
            'code' => 'LT-0200',
            'purchase_date' => '2025-01-31',
            'warranty_duration_months' => 3,
            'warranty_end_date' => '2025-04-30',
        ]);

        $this->actingAs($actor)->post(route('asset-register.assets.store'), array_replace(
            $this->assetPayload($typeId),
            [
                'code' => 'LT-0201',
                'purchase_date' => '2025-01-31',
                'warranty_duration_preset' => 'custom',
                'warranty_duration_custom_months' => '18',
                'warranty_duration_months' => '',
                'warranty_end_date' => '2026-03-15',
            ],
        ))->assertRedirect();

        $this->assertDatabaseHas('asset_register_assets', [
            'account_id' => $account->id,
            'code' => 'LT-0201',
            'warranty_duration_months' => 18,
            'warranty_end_date' => '2026-03-15',
        ]);
    }

    public function test_selected_warranty_duration_requires_a_purchase_date(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), array_replace($this->assetPayload($typeId), [
                'code' => 'LT-0200',
                'warranty_duration_preset' => '3',
                'warranty_duration_months' => '',
            ]))
            ->assertSessionHasErrors('purchase_date');

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), array_replace($this->assetPayload($typeId), [
                'code' => 'LT-0201',
                'purchase_date' => '2025-01-31',
                'warranty_duration_preset' => 'custom',
                'warranty_duration_custom_months' => '0',
            ]))
            ->assertSessionHasErrors('warranty_duration_custom_months');

        self::assertDatabaseMissing('asset_register_assets', [
            'account_id' => $account->id,
            'code' => 'LT-0200',
        ]);
    }

    public function test_asset_creation_saves_purchase_and_warranty_dates_with_one_history_entry(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);

        $response = $this->actingAs($actor)->post(
            route('asset-register.assets.store'),
            array_replace($this->assetPayload($typeId), [
                'code' => 'LT-0100',
                'purchase_date' => '2025-04-05',
                'warranty_end_date' => '2026-04-05',
            ]),
        );

        $assetId = (int) DB::table('asset_register_assets')->where('account_id', $account->id)->value('id');
        $response->assertRedirect(route('asset-register.assets.show', $assetId));
        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'purchase_date' => '2025-04-05',
            'warranty_end_date' => '2026-04-05',
        ]);
        self::assertSame(1, DB::table('asset_register_histories')
            ->where('asset_id', $assetId)
            ->where('action', 'added')
            ->count());
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

    public function test_asset_update_saves_purchase_and_warranty_dates_and_logs_old_and_new_values(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001');
        DB::table('asset_register_assets')->where('id', $assetId)->update([
            'purchase_date' => '2025-04-05',
            'warranty_end_date' => '2026-04-05',
        ]);

        $response = $this->actingAs($actor)->put(
            route('asset-register.assets.update', $assetId),
            array_replace($this->assetPayload($typeId), [
                'code' => 'CHANGED',
                'purchase_date' => '2025-05-06',
                'warranty_duration_preset' => '12',
                'warranty_duration_months' => '',
                'warranty_end_date' => '2026-05-06',
            ]),
        );

        $response->assertRedirect(route('asset-register.assets.show', $assetId));
        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'code' => 'LT-0001',
            'purchase_date' => '2025-05-06',
            'warranty_duration_months' => 12,
            'warranty_end_date' => '2026-05-06',
        ]);
        $history = DB::table('asset_register_histories')
            ->where('asset_id', $assetId)
            ->where('action', 'updated')
            ->sole();
        self::assertSame([
            'purchase_date' => ['old' => '2025-04-05', 'new' => '2025-05-06'],
            'warranty_end_date' => ['old' => '2026-04-05', 'new' => '2026-05-06'],
            'warranty_duration_months' => ['old' => null, 'new' => '12'],
        ], json_decode($history->description, true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_asset_update_can_change_type_without_changing_its_asset_code(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $oldTypeId = $this->insertType((int) $account->id, name: 'Laptop', prefix: 'LT');
        $newTypeId = $this->insertType((int) $account->id, name: 'Computer', prefix: 'PC');
        $assetId = $this->insertAsset((int) $account->id, $oldTypeId, 'LT-0001');

        $this->actingAs($actor)
            ->put(route('asset-register.assets.update', $assetId), array_replace(
                $this->assetPayload($newTypeId),
                ['code' => 'PC-0001'],
            ))
            ->assertRedirect(route('asset-register.assets.show', $assetId));

        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'asset_type_id' => $newTypeId,
            'code' => 'LT-0001',
        ]);
        $history = DB::table('asset_register_histories')
            ->where('asset_id', $assetId)
            ->where('action', 'updated')
            ->sole();
        self::assertSame([
            'asset_type_id' => ['old' => 'Laptop', 'new' => 'Computer'],
        ], json_decode($history->description, true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_asset_update_rejects_warranty_end_date_before_purchase_date(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001');

        $this->actingAs($actor)
            ->put(route('asset-register.assets.update', $assetId), array_replace($this->assetPayload($typeId), [
                'purchase_date' => '2026-10-20',
                'warranty_end_date' => '2026-10-19',
            ]))
            ->assertSessionHasErrors('warranty_end_date');

        self::assertSame(0, DB::table('asset_register_histories')
            ->where('asset_id', $assetId)
            ->where('action', 'updated')
            ->count());
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

    public function test_registry_viewer_cannot_open_or_submit_asset_edits(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view']);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001');

        $this->actingAs($actor)
            ->get(route('asset-register.assets.edit', $assetId))
            ->assertForbidden();
        $this->actingAs($actor)
            ->put(route('asset-register.assets.update', $assetId), $this->assetPayload($typeId))
            ->assertForbidden();
    }

    public function test_manager_cannot_open_or_update_an_asset_owned_by_another_account(): void
    {
        [$actor] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);
        [, $otherAccount] = $this->makeAccountWithFullAccessOwner('foreign asset edit');
        $typeId = $this->insertType((int) $otherAccount->id);
        $assetId = $this->insertAsset((int) $otherAccount->id, $typeId, 'LT-0001');

        $this->actingAs($actor)
            ->get(route('asset-register.assets.edit', $assetId))
            ->assertNotFound();
        $this->actingAs($actor)
            ->put(route('asset-register.assets.update', $assetId), $this->assetPayload($typeId))
            ->assertNotFound();
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
            ->assertOk()
            ->assertSee('class="ar-history-list"', false)
            ->assertSee('role="region"', false)
            ->assertSee('tabindex="0"', false);
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
            ->assertSee('class="ar-history-list"', false)
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

    public function test_pending_recipient_can_open_details_without_manager_only_notes_or_history(): void
    {
        [$recipient, $account] = $this->actorWithPermissions(['asset-register.self.use']);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'in_use');
        DB::table('asset_register_assets')->where('id', $assetId)->update([
            'note' => 'MANAGER_ONLY_ASSET_NOTE',
        ]);
        DB::table('asset_register_handovers')->insert([
            'account_id' => $account->id,
            'asset_id' => $assetId,
            'recipient_id' => $recipient->id,
            'status' => 'pending',
            'handed_over_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('asset_register_histories')->insert([
            'account_id' => $account->id,
            'asset_id' => $assetId,
            'user_id' => $recipient->id,
            'action' => 'reported',
            'description' => 'RECIPIENT_VISIBLE_HISTORY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $manager = User::factory()->create();
        DB::table('asset_register_histories')->insert([
            'account_id' => $account->id,
            'asset_id' => $assetId,
            'user_id' => $manager->id,
            'action' => 'updated',
            'description' => 'MANAGER_ONLY_HISTORY',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->actingAs($recipient)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertOk()
            ->assertDontSee(__('asset-register::asset-register.mine.confirm_received'))
            ->assertDontSee('data-ar-open-action-dialog', false)
            ->assertSee('RECIPIENT_VISIBLE_HISTORY')
            ->assertDontSee('MANAGER_ONLY_HISTORY')
            ->assertDontSee('MANAGER_ONLY_ASSET_NOTE')
            ->assertDontSee('ar-edit-asset');
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

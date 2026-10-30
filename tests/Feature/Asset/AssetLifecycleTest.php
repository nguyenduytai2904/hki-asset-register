<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

require_once dirname(__DIR__, 2).'/Support/InteractsWithAssetRegister.php';

use Illuminate\Support\Facades\DB;
use Hki\AssetRegister\Tests\Support\InteractsWithAssetRegister;
use Tests\TestCase;

final class AssetLifecycleTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_manager_can_change_status_and_append_one_history_entry(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$assetId, $code] = $this->createAsset((int) $account->id, 'available');

        $this->actingAs($actor)
            ->post(route('asset-register.assets.status', $assetId), [
                '_asset_register_dialog' => 'status',
                'status' => 'in_repair',
                'reason' => 'Sent for repair',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId));

        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'code' => $code,
            'status' => 'in_repair',
            'current_keeper_id' => null,
        ]);
        $history = DB::table('asset_register_histories')->where('asset_id', $assetId)->first();
        self::assertSame('status_changed', $history?->action);
        self::assertSame([
            'from' => 'available',
            'to' => 'in_repair',
            'reason' => 'Sent for repair',
        ], json_decode((string) $history?->description, true));

        $this->actingAs($actor)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertOk()
            ->assertSee(__('asset-register::asset-register.actions.reason').':')
            ->assertSee('data-ar-latest-status-change', false)
            ->assertSee('Sent for repair')
            ->assertSee('class="ar-history-list" role="region"', false)
            ->assertSee('tabindex="0"', false);
    }

    public function test_asset_detail_summarizes_only_the_latest_status_change_reason(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$assetId] = $this->createAsset((int) $account->id, 'available');

        $this->actingAs($actor)->post(route('asset-register.assets.status', $assetId), [
            '_asset_register_dialog' => 'status',
            'status' => 'in_repair',
            'reason' => 'First status reason',
        ])->assertRedirect();
        DB::table('asset_register_histories')
            ->where('asset_id', $assetId)
            ->update(['created_at' => now()->subMinute()]);

        $this->actingAs($actor)->post(route('asset-register.assets.status', $assetId), [
            '_asset_register_dialog' => 'status',
            'status' => 'available',
            'reason' => 'Latest status reason',
        ])->assertRedirect();

        $response = $this->actingAs($actor)->get(route('asset-register.assets.show', $assetId));
        $response->assertOk()->assertSee('First status reason');

        $content = (string) $response->getContent();
        self::assertSame(
            1,
            preg_match('/<span class="ar-status-reason"[^>]*>(.*?)<\\/span>\\s*<\\/span>/s', $content, $summary),
        );
        self::assertStringContainsString('Sẵn sàng', $summary[1]);
        self::assertStringContainsString('Latest status reason', $summary[1]);
        self::assertStringNotContainsString('First status reason', $summary[1]);
        self::assertStringNotContainsString($actor->name, $summary[1]);
        self::assertStringNotContainsString('<section class="ar-latest-status-change"', $content);
        self::assertStringNotContainsString('<details class="ar-latest-status-popover"', $content);
    }

    public function test_status_change_from_in_use_closes_open_handover_and_clears_keeper(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$keeper] = $this->addMemberWithPermissions($account, ['asset-register.self.use'], 'asset keeper');
        [$assetId] = $this->createAsset((int) $account->id, 'in_use', (int) $keeper->id);
        $handoverId = $this->insertHandover((int) $account->id, $assetId, (int) $keeper->id, 'confirmed');
        DB::table('asset_register_handovers')->where('id', $handoverId)->update([
            'note' => 'Original assignment note',
        ]);

        $this->actingAs($actor)
            ->post(route('asset-register.assets.status', $assetId), [
                '_asset_register_dialog' => 'status',
                'status' => 'broken',
                'reason' => 'Screen is damaged',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId));

        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'status' => 'broken',
            'current_keeper_id' => null,
        ]);
        $this->assertDatabaseHas('asset_register_handovers', [
            'id' => $handoverId,
            'status' => 'closed',
            'note' => "Original assignment note\n\n".__('asset-register::asset-register.actions.status_closure_note', [
                'status' => __('asset-register::asset-register.asset_status.broken'),
                'reason' => 'Screen is damaged',
            ]),
        ]);
        self::assertNotNull(DB::table('asset_register_handovers')->where('id', $handoverId)->value('closed_at'));
        self::assertSame(1, DB::table('asset_register_histories')->where('asset_id', $assetId)->count());
    }

    public function test_disallowed_status_transition_does_not_change_asset_or_history(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$assetId] = $this->createAsset((int) $account->id, 'broken');

        $this->actingAs($actor)
            ->from(route('asset-register.assets.show', $assetId))
            ->post(route('asset-register.assets.status', $assetId), [
                '_asset_register_dialog' => 'status',
                'status' => 'lost',
                'reason' => 'Asset location changed',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('asset_register_assets', ['id' => $assetId, 'status' => 'broken']);
        self::assertSame(0, DB::table('asset_register_histories')->where('asset_id', $assetId)->count());

        $this->actingAs($actor)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertOk()
            ->assertSee('data-ar-failed-dialog="status"', false)
            ->assertSee('data-ar-action-dialog="status"', false);
    }

    public function test_disposal_dialog_uses_the_plugin_datepicker_hook(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$assetId] = $this->createAsset((int) $account->id, 'available');

        $this->actingAs($actor)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertOk()
            ->assertSee('data-ar-submit-form', false)
            ->assertSee('data-ar-action-feedback', false)
            ->assertSee('data-ar-datepicker="date"', false)
            ->assertSee(__('asset-register::asset-register.actions.disposal_date_hint'))
            ->assertDontSee('data-ar-latest-status-change', false)
            ->assertDontSee('data-hk-datepicker="date"', false);
    }

    public function test_status_change_rejects_an_inconsistent_open_handover(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$keeper] = $this->addMemberWithPermissions($account, ['asset-register.self.use'], 'asset keeper');
        [$otherRecipient] = $this->addMemberWithPermissions($account, ['asset-register.self.use'], 'another recipient');
        [$assetId] = $this->createAsset((int) $account->id, 'in_use', (int) $keeper->id);
        $handoverId = $this->insertHandover((int) $account->id, $assetId, (int) $otherRecipient->id, 'confirmed');

        $this->actingAs($actor)
            ->from(route('asset-register.assets.show', $assetId))
            ->post(route('asset-register.assets.status', $assetId), [
                '_asset_register_dialog' => 'status',
                'status' => 'broken',
                'reason' => 'Screen is damaged',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'status' => 'in_use',
            'current_keeper_id' => $keeper->id,
        ]);
        $this->assertDatabaseHas('asset_register_handovers', [
            'id' => $handoverId,
            'status' => 'confirmed',
            'closed_at' => null,
        ]);
        self::assertSame(0, DB::table('asset_register_histories')->where('asset_id', $assetId)->count());
    }

    public function test_status_change_requires_a_reason_and_manage_permission(): void
    {
        [$manager, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$assetId] = $this->createAsset((int) $account->id, 'available');

        $this->actingAs($manager)
            ->from(route('asset-register.assets.show', $assetId))
            ->post(route('asset-register.assets.status', $assetId), [
                '_asset_register_dialog' => 'status',
                'status' => 'lost',
                'reason' => 'bad',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId))
            ->assertSessionHasErrors('reason');

        [$viewer] = $this->addMemberWithPermissions($account, ['asset-register.assets.view'], 'registry viewer');
        $this->actingAs($viewer)
            ->post(route('asset-register.assets.status', $assetId), [
                '_asset_register_dialog' => 'status',
                'status' => 'lost',
                'reason' => 'Asset cannot be found',
            ])
            ->assertForbidden();
    }

    public function test_manager_can_dispose_an_asset_without_reusing_its_code(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$assetId, $code] = $this->createAsset((int) $account->id, 'available', null, '2020-01-15');
        $disposedAt = now()->subDay()->toDateString();

        $this->actingAs($actor)
            ->post(route('asset-register.assets.dispose', $assetId), [
                '_asset_register_dialog' => 'dispose',
                'disposed_at' => $disposedAt,
                'disposal_method' => 'sale',
                'disposal_basis' => 'The equipment is beyond repair.',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId));

        $this->assertDatabaseHas('asset_register_assets', [
            'id' => $assetId,
            'code' => $code,
            'status' => 'disposed',
            'disposed_at' => $disposedAt,
            'disposal_method' => 'sale',
            'disposal_basis' => 'The equipment is beyond repair.',
        ]);
        $this->assertDatabaseHas('asset_register_histories', [
            'asset_id' => $assetId,
            'action' => 'disposed',
        ]);
        self::assertSame(1, DB::table('asset_register_histories')->where('asset_id', $assetId)->count());
    }

    public function test_disposal_rejects_in_use_assets_and_invalid_dates(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.assets.view', 'asset-register.assets.manage']);
        [$keeper] = $this->addMemberWithPermissions($account, ['asset-register.self.use'], 'asset keeper');
        [$inUseAssetId] = $this->createAsset((int) $account->id, 'in_use', (int) $keeper->id);

        $this->actingAs($actor)
            ->from(route('asset-register.assets.show', $inUseAssetId))
            ->post(route('asset-register.assets.dispose', $inUseAssetId), [
                '_asset_register_dialog' => 'dispose',
                'disposed_at' => now()->subDay()->toDateString(),
                'disposal_method' => 'sale',
                'disposal_basis' => 'No longer needed.',
            ])
            ->assertRedirect(route('asset-register.assets.show', $inUseAssetId))
            ->assertSessionHasErrors('asset');

        [$assetId] = $this->createAsset((int) $account->id, 'available', null, '2024-01-15');
        $this->actingAs($actor)
            ->from(route('asset-register.assets.show', $assetId))
            ->post(route('asset-register.assets.dispose', $assetId), [
                '_asset_register_dialog' => 'dispose',
                'disposed_at' => now()->addDay()->toDateString(),
                'disposal_method' => 'sale',
                'disposal_basis' => 'Sold to a recycler.',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId))
            ->assertSessionHasErrors('disposed_at');

        $this->actingAs($actor)
            ->from(route('asset-register.assets.show', $assetId))
            ->post(route('asset-register.assets.dispose', $assetId), [
                '_asset_register_dialog' => 'dispose',
                'disposed_at' => '2023-12-31',
                'disposal_method' => 'sale',
                'disposal_basis' => 'Sold to a recycler.',
            ])
            ->assertRedirect(route('asset-register.assets.show', $assetId))
            ->assertSessionHasErrors('disposed_at');

        $this->assertDatabaseHas('asset_register_assets', ['id' => $assetId, 'status' => 'available']);
        self::assertSame(0, DB::table('asset_register_histories')->where('asset_id', $assetId)->count());
    }

    private function createAsset(int $accountId, string $status, ?int $keeperId = null, ?string $purchaseDate = null): array
    {
        $typeId = DB::table('asset_register_types')
            ->where('account_id', $accountId)
            ->where('prefix', 'LT')
            ->value('id');
        if ($typeId === null) {
            $typeId = DB::table('asset_register_types')->insertGetId([
                'account_id' => $accountId,
                'name' => 'Laptop',
                'prefix' => 'LT',
                'status' => 'active',
                'next_code_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $sequence = DB::table('asset_register_assets')->where('account_id', $accountId)->count() + 1;
        $code = 'LT-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        $assetId = DB::table('asset_register_assets')->insertGetId([
            'account_id' => $accountId,
            'asset_type_id' => $typeId,
            'code' => $code,
            'name' => $code,
            'condition' => 'good',
            'status' => $status,
            'current_keeper_id' => $keeperId,
            'purchase_date' => $purchaseDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$assetId, $code];
    }

    private function insertHandover(int $accountId, int $assetId, int $recipientId, string $status): int
    {
        return DB::table('asset_register_handovers')->insertGetId([
            'account_id' => $accountId,
            'asset_id' => $assetId,
            'recipient_id' => $recipientId,
            'status' => $status,
            'handed_over_at' => now(),
            'confirmed_at' => $status === 'confirmed' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

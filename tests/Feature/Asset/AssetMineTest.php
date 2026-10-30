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

final class AssetMineTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_member_sees_only_own_pending_and_confirmed_assets_in_required_order(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.self.use']);
        $typeId = $this->insertType((int) $account->id);
        $pendingAssetId = $this->insertAsset((int) $account->id, $typeId, 'AR-PENDING', 'in_use');
        $heldAssetId = $this->insertAsset((int) $account->id, $typeId, 'AR-HELD', 'in_use', (int) $actor->id);
        $otherMember = User::factory()->create(['name' => 'Another member']);
        $otherAssetId = $this->insertAsset((int) $account->id, $typeId, 'AR-OTHER', 'in_use', (int) $otherMember->id);
        [, $otherAccount] = $this->makeAccountWithFullAccessOwner('other mine account');
        $otherTypeId = $this->insertType((int) $otherAccount->id, 'active', 'Other type', 'OT');
        $foreignAssetId = $this->insertAsset((int) $otherAccount->id, $otherTypeId, 'AR-FOREIGN', 'in_use', (int) $actor->id);

        $this->insertHandover((int) $account->id, $pendingAssetId, (int) $actor->id, 'pending', now());
        $this->insertHandover((int) $account->id, $heldAssetId, (int) $actor->id, 'confirmed', now()->subDay());
        $this->insertHandover((int) $account->id, $otherAssetId, (int) $otherMember->id, 'confirmed', now());
        $this->insertHandover((int) $otherAccount->id, $foreignAssetId, (int) $actor->id, 'confirmed', now());
        $this->insertHandoverHistory((int) $account->id, $pendingAssetId, (int) $otherMember->id);

        $autoRead = Mockery::mock(NotificationAutoReadServiceInterface::class);
        $autoRead->shouldReceive('markSubjectsRead')
            ->once()
            ->with((int) $actor->id, Asset::NOTIFICATION_SUBJECT_TYPE, [$pendingAssetId, $heldAssetId])
            ->andReturn(2);
        $this->app->instance(NotificationAutoReadServiceInterface::class, $autoRead);

        $response = $this->actingAs($actor)->get(route('asset-register.mine'));

        $response->assertOk()
            ->assertSee('AR-PENDING')
            ->assertSee('AR-HELD')
            ->assertSee(__('asset-register::asset-register.mine.pending', ['count' => 1]))
            ->assertDontSee('AR-OTHER')
            ->assertDontSee('AR-FOREIGN')
            ->assertDontSee('data-ar-open-action-dialog', false)
            ->assertDontSee('Dữ liệu minh hoạ');

        preg_match('/<tbody>(.*?)<\\/tbody>/s', (string) $response->getContent(), $table);
        self::assertArrayHasKey(1, $table);
        self::assertLessThan(strpos($table[1], 'AR-HELD'), strpos($table[1], 'AR-PENDING'));
        self::assertSame(15, $response->viewData('assets')->perPage());
        self::assertSame(2, $response->viewData('assets')->total());
    }

    public function test_mine_paginates_pending_assets_first_and_clamps_to_last_page(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.self.use']);
        $typeId = $this->insertType((int) $account->id);
        $olderPendingId = $this->insertAsset((int) $account->id, $typeId, 'AR-PENDING-OLDER', 'in_use');
        $newerPendingId = $this->insertAsset((int) $account->id, $typeId, 'AR-PENDING-NEWER', 'in_use');
        $heldAssetId = $this->insertAsset((int) $account->id, $typeId, 'AR-HELD', 'in_use', (int) $actor->id);
        $this->insertHandover((int) $account->id, $olderPendingId, (int) $actor->id, 'pending', now()->subDays(2));
        $this->insertHandover((int) $account->id, $newerPendingId, (int) $actor->id, 'pending', now()->subDay());
        $this->insertHandover((int) $account->id, $heldAssetId, (int) $actor->id, 'confirmed', now());

        $autoRead = Mockery::mock(NotificationAutoReadServiceInterface::class);
        $autoRead->shouldReceive('markSubjectsRead')
            ->once()
            ->with((int) $actor->id, Asset::NOTIFICATION_SUBJECT_TYPE, [$olderPendingId])
            ->andReturn(1);
        $autoRead->shouldReceive('markSubjectsRead')
            ->once()
            ->with((int) $actor->id, Asset::NOTIFICATION_SUBJECT_TYPE, [$heldAssetId])
            ->andReturn(1);
        $this->app->instance(NotificationAutoReadServiceInterface::class, $autoRead);

        $pageTwo = $this->actingAs($actor)->get(route('asset-register.mine', ['per_page' => 1, 'page' => 2]));
        $pageTwo->assertOk()
            ->assertSee('AR-PENDING-OLDER')
            ->assertSee(__('asset-register::asset-register.mine.pending', ['count' => 2]))
            ->assertDontSee('AR-PENDING-NEWER');
        self::assertSame(2, $pageTwo->viewData('assets')->currentPage());
        self::assertSame(3, $pageTwo->viewData('assets')->total());

        $lastPage = $this->actingAs($actor)->get(route('asset-register.mine', ['per_page' => 1, 'page' => 99]));

        $lastPage->assertOk()
            ->assertSee('AR-HELD')
            ->assertDontSee('AR-PENDING-OLDER');
        self::assertSame(3, $lastPage->viewData('assets')->currentPage());
    }

    public function test_mine_page_size_is_clamped_to_supported_range(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.self.use']);
        $autoRead = Mockery::mock(NotificationAutoReadServiceInterface::class);
        $autoRead->shouldNotReceive('markSubjectsRead');
        $this->app->instance(NotificationAutoReadServiceInterface::class, $autoRead);

        $response = $this->actingAs($actor)
            ->get(route('asset-register.mine', ['per_page' => 500]));

        $response->assertOk();
        self::assertSame(100, $response->viewData('assets')->perPage());
    }

    public function test_empty_mine_page_has_a_truthful_empty_state_and_no_preview_route(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.self.use']);
        $autoRead = Mockery::mock(NotificationAutoReadServiceInterface::class);
        $autoRead->shouldNotReceive('markSubjectsRead');
        $this->app->instance(NotificationAutoReadServiceInterface::class, $autoRead);

        $this->actingAs($actor)
            ->get(route('asset-register.mine'))
            ->assertOk()
            ->assertSee('Bạn chưa giữ tài sản nào của công ty.')
            ->assertDontSee('Dữ liệu minh hoạ');

        $this->actingAs($actor)
            ->get('/asset-register/mine/preview/AR-FAKE')
            ->assertNotFound();
    }

    public function test_member_without_self_service_permission_cannot_open_mine_page(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.assets.view']);

        $this->actingAs($actor)->get(route('asset-register.mine'))->assertForbidden();
    }

    public function test_self_service_view_does_not_reveal_another_members_status_change_reason(): void
    {
        [$actor, $account] = $this->actorWithPermissions(['asset-register.self.use']);
        $typeId = $this->insertType((int) $account->id);
        $assetId = $this->insertAsset((int) $account->id, $typeId, 'AR-OWN', 'in_use', (int) $actor->id);
        $this->insertHandover((int) $account->id, $assetId, (int) $actor->id, 'confirmed', now());
        $otherActor = User::factory()->create(['name' => 'Another manager']);
        DB::table('asset_register_histories')->insert([
            'account_id' => $account->id,
            'asset_id' => $assetId,
            'user_id' => $otherActor->id,
            'action' => 'status_changed',
            'description' => json_encode([
                'from' => 'available',
                'to' => 'in_repair',
                'reason' => 'Private manager reason',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor)
            ->get(route('asset-register.assets.show', $assetId))
            ->assertOk()
            ->assertDontSee('data-ar-latest-status-change', false)
            ->assertDontSee('Private manager reason');
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

    private function insertAsset(int $accountId, int $typeId, string $code, string $status, ?int $keeperId = null): int
    {
        return DB::table('asset_register_assets')->insertGetId([
            'account_id' => $accountId,
            'asset_type_id' => $typeId,
            'code' => $code,
            'name' => $code,
            'condition' => 'good',
            'status' => $status,
            'current_keeper_id' => $keeperId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertHandover(int $accountId, int $assetId, int $recipientId, string $status, mixed $handedOverAt): void
    {
        DB::table('asset_register_handovers')->insert([
            'account_id' => $accountId,
            'asset_id' => $assetId,
            'recipient_id' => $recipientId,
            'status' => $status,
            'condition_on_handover' => 'good',
            'handed_over_at' => $handedOverAt,
            'confirmed_at' => $status === 'confirmed' ? $handedOverAt : null,
            'created_at' => $handedOverAt,
            'updated_at' => $handedOverAt,
        ]);
    }

    private function insertHandoverHistory(int $accountId, int $assetId, int $userId): void
    {
        DB::table('asset_register_histories')->insert([
            'account_id' => $accountId,
            'asset_id' => $assetId,
            'user_id' => $userId,
            'action' => 'handed_over',
            'description' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

require_once dirname(__DIR__, 2).'/Support/InteractsWithAssetRegister.php';

use Hki\AssetRegister\Tests\Support\InteractsWithAssetRegister;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AssetCatalogTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_asset_manager_can_open_settings_and_create_and_update_asset_types(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());
        $this->actingAs($actor)
            ->get(route('asset-register.settings'))
            ->assertOk()
            ->assertSee('data-ar-first-code', false)
            ->assertSee(__('asset-register::asset-register.settings.first_code_preview'));

        $this->actingAs($actor)
            ->post(route('asset-register.settings.asset-types.store'), [
                'name' => 'Laptop',
                'prefix' => 'lt',
                'icon' => 'laptop',
                'description' => 'Portable computers',
            ])
            ->assertRedirect(route('asset-register.settings'));

        $type = DB::table('asset_register_types')->where('account_id', $account->id)->where('name', 'Laptop')->first();
        self::assertNotNull($type);
        self::assertSame('LT', $type->prefix);
        self::assertSame('active', $type->status);

        $this->actingAs($actor)
            ->get(route('asset-register.settings'))
            ->assertSee('LT-0001');

        $this->actingAs($actor)
            ->put(route('asset-register.settings.asset-types.update', $type->id), [
                'name' => 'Portable computer',
                'prefix' => 'LT',
                'icon' => 'computer',
                'description' => 'Updated description',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('asset-register.settings'));

        $updated = DB::table('asset_register_types')->find($type->id);
        self::assertNotNull($updated);
        self::assertSame('Portable computer', $updated->name);
        self::assertSame('computer', $updated->icon);
        self::assertSame('Updated description', $updated->description);
        self::assertSame('inactive', $updated->status);
    }

    public function test_asset_type_validation_rejects_duplicate_name_prefix_and_unknown_icon(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());
        $this->insertType((int) $account->id, 'Laptop', 'LT');
        [, $otherAccount] = $this->makeAccountWithFullAccessOwner('other type catalog');
        $otherTypeId = $this->insertType((int) $otherAccount->id, 'Laptop', 'LT');
        self::assertDatabaseHas('asset_register_types', ['id' => $otherTypeId, 'account_id' => $otherAccount->id]);

        $this->actingAs($actor)
            ->post(route('asset-register.settings.asset-types.store'), [
                'name' => 'laptop',
                'prefix' => 'lt',
                'icon' => 'laptop',
            ])
            ->assertSessionHasErrors(['name', 'prefix']);

        $this->actingAs($actor)
            ->post(route('asset-register.settings.asset-types.store'), [
                'name' => 'Monitor',
                'prefix' => 'MN',
                'icon' => 'unapproved_icon',
            ])
            ->assertSessionHasErrors(['icon']);
    }

    public function test_asset_type_in_use_cannot_change_prefix_or_be_deleted_but_unused_type_can_be_deleted(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());
        $typeId = $this->insertType((int) $account->id, 'Laptop', 'LT');
        $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'disposed');

        $this->actingAs($actor)
            ->get(route('asset-register.settings'))
            ->assertSee(
                '<button class="ar-button ar-button-danger" type="button" disabled>'
                    .e(__('asset-register::asset-register.settings.delete')).'</button>',
                false,
            )
            ->assertSee(__('asset-register::asset-register.settings.cannot_delete_in_use', ['count' => 1]));

        $this->actingAs($actor)
            ->put(route('asset-register.settings.asset-types.update', $typeId), [
                'name' => 'Laptop',
                'prefix' => 'PC',
                'icon' => 'laptop',
                'status' => 'inactive',
            ])
            ->assertSessionHasErrors('prefix');

        $this->actingAs($actor)
            ->delete(route('asset-register.settings.asset-types.delete', $typeId))
            ->assertSessionHasErrors('type');
        self::assertDatabaseHas('asset_register_types', ['id' => $typeId, 'prefix' => 'LT']);

        $unusedTypeId = $this->insertType((int) $account->id, 'Monitor', 'MN');
        $this->actingAs($actor)
            ->delete(route('asset-register.settings.asset-types.delete', $unusedTypeId))
            ->assertRedirect(route('asset-register.settings'));
        self::assertDatabaseMissing('asset_register_types', ['id' => $unusedTypeId]);
    }

    public function test_locations_support_create_update_delete_and_account_unique_names(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());
        [, $otherAccount] = $this->makeAccountWithFullAccessOwner('other location catalog');
        $otherLocationId = $this->insertLocation((int) $otherAccount->id, 'Server room');
        self::assertDatabaseHas('asset_register_locations', ['id' => $otherLocationId, 'account_id' => $otherAccount->id]);

        $this->actingAs($actor)
            ->post(route('asset-register.settings.locations.store'), [
                'name' => 'Server room',
                'description' => 'Main office',
            ])
            ->assertRedirect(route('asset-register.settings'));

        $location = DB::table('asset_register_locations')->where('account_id', $account->id)->where('name', 'Server room')->first();
        self::assertNotNull($location);
        self::assertSame('active', $location->status);

        $this->actingAs($actor)
            ->post(route('asset-register.settings.locations.store'), [
                'name' => 'SERVER ROOM',
                'description' => '',
            ])
            ->assertSessionHasErrors(['name']);

        $this->actingAs($actor)
            ->put(route('asset-register.settings.locations.update', $location->id), [
                'name' => 'Data center',
                'description' => 'Updated location',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('asset-register.settings'));
        self::assertDatabaseHas('asset_register_locations', [
            'id' => $location->id,
            'name' => 'Data center',
            'description' => 'Updated location',
            'status' => 'inactive',
        ]);

        $this->actingAs($actor)
            ->delete(route('asset-register.settings.locations.delete', $location->id))
            ->assertRedirect(route('asset-register.settings'));
        self::assertDatabaseMissing('asset_register_locations', ['id' => $location->id]);
    }

    public function test_location_in_use_cannot_be_deleted_even_when_asset_is_disposed(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());
        $typeId = $this->insertType((int) $account->id, 'Laptop', 'LT');
        $locationId = DB::table('asset_register_locations')->insertGetId([
            'account_id' => $account->id,
            'name' => 'Server room',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'disposed', $locationId);

        $this->actingAs($actor)
            ->get(route('asset-register.settings'))
            ->assertSee(
                '<button class="ar-button ar-button-danger" type="button" disabled>'
                    .e(__('asset-register::asset-register.settings.delete')).'</button>',
                false,
            )
            ->assertSee(__('asset-register::asset-register.settings.cannot_delete_in_use', ['count' => 1]));

        $this->actingAs($actor)
            ->delete(route('asset-register.settings.locations.delete', $locationId))
            ->assertSessionHasErrors('location');
        self::assertDatabaseHas('asset_register_locations', ['id' => $locationId, 'status' => 'active']);

        $this->actingAs($actor)
            ->put(route('asset-register.settings.locations.update', $locationId), [
                'name' => 'Server room',
                'description' => '',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('asset-register.settings'));
        self::assertDatabaseHas('asset_register_locations', ['id' => $locationId, 'status' => 'inactive']);
    }

    public function test_catalog_routes_cannot_mutate_records_from_another_account(): void
    {
        [$actor] = $this->actorWithPermissions($this->managerPermissions());
        [, $otherAccount] = $this->makeAccountWithFullAccessOwner('isolated catalog');
        $typeId = $this->insertType((int) $otherAccount->id, 'Laptop', 'LT');
        $locationId = $this->insertLocation((int) $otherAccount->id, 'Server room');

        $this->actingAs($actor)
            ->put(route('asset-register.settings.asset-types.update', $typeId), [
                'name' => 'Compromised',
                'prefix' => 'LT',
                'icon' => 'laptop',
                'status' => 'active',
            ])
            ->assertNotFound();
        $this->actingAs($actor)
            ->delete(route('asset-register.settings.asset-types.delete', $typeId))
            ->assertNotFound();
        $this->actingAs($actor)
            ->put(route('asset-register.settings.locations.update', $locationId), [
                'name' => 'Compromised',
                'description' => '',
                'status' => 'active',
            ])
            ->assertNotFound();
        $this->actingAs($actor)
            ->delete(route('asset-register.settings.locations.delete', $locationId))
            ->assertNotFound();

        self::assertDatabaseHas('asset_register_types', ['id' => $typeId, 'name' => 'Laptop']);
        self::assertDatabaseHas('asset_register_locations', ['id' => $locationId, 'name' => 'Server room']);
    }

    /** @return list<string> */
    private function managerPermissions(): array
    {
        return [
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ];
    }

    private function insertType(int $accountId, string $name, string $prefix): int
    {
        return DB::table('asset_register_types')->insertGetId([
            'account_id' => $accountId,
            'name' => $name,
            'prefix' => $prefix,
            'icon' => 'laptop',
            'status' => 'active',
            'next_code_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertLocation(int $accountId, string $name): int
    {
        return DB::table('asset_register_locations')->insertGetId([
            'account_id' => $accountId,
            'name' => $name,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertAsset(int $accountId, int $typeId, string $code, string $status, ?int $locationId = null): int
    {
        return DB::table('asset_register_assets')->insertGetId([
            'account_id' => $accountId,
            'asset_type_id' => $typeId,
            'asset_location_id' => $locationId,
            'code' => $code,
            'name' => $code,
            'condition' => 'good',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

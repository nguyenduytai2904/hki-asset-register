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

    public function test_catalog_row_actions_open_edit_dialogs_and_success_uses_no_inline_banner(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());
        $typeId = $this->insertType((int) $account->id, 'Laptop', 'LT');
        $locationId = $this->insertLocation((int) $account->id, 'Server room');

        $this->actingAs($actor)
            ->withSession(['success' => 'Catalog item deleted.'])
            ->get(route('asset-register.settings'))
            ->assertOk()
            ->assertSee('data-ar-open-dialog="ar-edit-type-'.$typeId.'-dialog"', false)
            ->assertSee('id="ar-edit-type-'.$typeId.'-dialog"', false)
            ->assertSee('data-ar-open-dialog="ar-edit-location-'.$locationId.'-dialog"', false)
            ->assertSee('id="ar-edit-location-'.$locationId.'-dialog"', false)
            ->assertSee('name="_asset_register_dialog" value="edit-type-'.$typeId.'"', false)
            ->assertSee('name="_asset_register_dialog" value="edit-location-'.$locationId.'"', false)
            ->assertDontSee('<details', false)
            ->assertDontSee('class="ar-alert ar-alert-success"', false);
    }

    public function test_manager_can_create_and_update_an_asset_type(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());

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

    public function test_referenced_asset_type_prefix_cannot_change_and_type_cannot_be_deleted(): void
    {
        [$actor, $account] = $this->actorWithPermissions($this->managerPermissions());
        $typeId = $this->insertType((int) $account->id, 'Laptop', 'LT');
        $this->insertAsset((int) $account->id, $typeId, 'LT-0001', 'disposed');

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

    public function test_location_name_is_account_unique_and_location_can_be_updated_and_deleted(): void
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

    public function test_location_referenced_by_disposed_asset_cannot_be_deleted(): void
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

    public function test_manager_cannot_update_or_delete_another_account_catalog_entries(): void
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

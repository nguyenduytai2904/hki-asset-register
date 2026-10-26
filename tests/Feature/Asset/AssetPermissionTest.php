<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

require_once dirname(__DIR__, 2).'/Support/InteractsWithAssetRegister.php';

use App\Enums\Account\AccountUserPermissionEnum;
use App\Models\AccountPlugin;
use App\Models\Plugin;
use App\Support\Account\AccountContext;
use App\Support\Rbac\AccountPermissionCatalogRegistry;
use Hki\AssetRegister\AssetRegisterModule;
use Hki\AssetRegister\Rbac\AssetRegisterPermissionProvider;
use Hki\AssetRegister\Tests\Support\InteractsWithAssetRegister;
use Tests\TestCase;

final class AssetPermissionTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_plugin_registers_its_permissions_and_default_asset_manager_role(): void
    {
        $provider = new AssetRegisterPermissionProvider;

        self::assertSame([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ], array_map(static fn ($permission): string => $permission->key, $provider->permissions()));

        $keys = app(AccountPermissionCatalogRegistry::class)->knownPermissionKeys();
        self::assertContains('asset-register.self.use', $keys);
        self::assertContains('asset-register.assets.view', $keys);
        self::assertContains('asset-register.assets.manage', $keys);

        $ownerRole = (new AssetRegisterModule)->defaultRoles()[0];
        self::assertSame('asset-register.asset_manager', $ownerRole->code);
        self::assertSame([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ], $ownerRole->permissions);
        self::assertTrue($ownerRole->isDefaultForAccountOwner);
    }

    public function test_asset_menu_redirects_to_the_first_allowed_page(): void
    {
        [$actor] = $this->actorWithPermissions([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/registry');
    }

    public function test_member_with_self_service_permission_cannot_access_registry_or_settings(): void
    {
        [$actor] = $this->actorWithPermissions([
            AccountUserPermissionEnum::VIEW->value,
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/mine');

        $this->actingAs($actor)
            ->get('/asset-register/mine')
            ->assertOk();

        $this->actingAs($actor)->get('/asset-register/registry')->assertForbidden();
        $this->actingAs($actor)->get('/asset-register/settings')->assertForbidden();
    }

    public function test_core_account_manager_can_access_registry_my_assets_and_settings(): void
    {
        [$actor] = $this->actorWithPermissions([
            AccountUserPermissionEnum::MANAGE->value,
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/registry');

        $this->actingAs($actor)
            ->get('/asset-register/registry')
            ->assertOk();
        $this->actingAs($actor)->get('/asset-register/mine')->assertOk();
        $this->actingAs($actor)->get('/asset-register/settings')->assertOk();
    }

    public function test_account_without_plugin_grant_is_blocked(): void
    {
        $this->requirePlugin(AssetRegisterModule::CODE);
        [, $account] = $this->makeAccountWithFullAccessOwner('ungranted');
        [$actor] = $this->addMemberWithPermissions($account, ['asset-register.assets.manage'], 'ungranted actor');
        AccountPlugin::query()->where('plugin_code', AssetRegisterModule::CODE)->delete();
        Plugin::query()->where('code', AssetRegisterModule::CODE)->delete();
        AccountContext::force((int) $account->id);

        $this->actingAs($actor)
            ->get('/asset-register/settings')
            ->assertForbidden();
    }

    public function test_suspended_member_is_blocked(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.manage',
        ]);
        $this->suspendMemberInAccount($actor, $account, 'Test');

        $this->actingAs($actor)
            ->get('/asset-register/settings')
            ->assertRedirect(route('accounts.index'));
    }

    public function test_manage_permission_without_view_permission_cannot_manage_assets_or_catalogs(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.assets.manage']);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/settings');

        $this->actingAs($actor)->get('/asset-register/registry')->assertForbidden();
        $this->actingAs($actor)->get('/asset-register/settings')->assertOk();
        $this->actingAs($actor)
            ->post(route('asset-register.settings.asset-types.store'), [])
            ->assertForbidden();
        $this->actingAs($actor)
            ->post(route('asset-register.settings.locations.store'), [])
            ->assertForbidden();
        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), [])
            ->assertForbidden();
    }

    public function test_registry_viewer_cannot_create_assets_or_manage_catalogs(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.assets.view']);

        $this->actingAs($actor)
            ->post(route('asset-register.settings.asset-types.store'), [])
            ->assertForbidden();

        $this->actingAs($actor)
            ->post(route('asset-register.settings.locations.store'), [])
            ->assertForbidden();

        $this->actingAs($actor)
            ->post(route('asset-register.assets.store'), [])
            ->assertForbidden();
    }
}

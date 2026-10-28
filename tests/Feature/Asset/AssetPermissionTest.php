<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

require_once dirname(__DIR__, 2).'/Support/InteractsWithAssetRegister.php';

use App\Enums\Account\AccountUserPermissionEnum;
use App\Models\AccountPlugin;
use App\Support\Account\AccountContext;
use App\Support\Rbac\AccountPermissionCatalogRegistry;
use App\Support\Plugin\PluginRegistry;
use Hki\AssetRegister\AssetRegisterModule;
use Hki\AssetRegister\Rbac\AssetRegisterPermissionProvider;
use Hki\AssetRegister\Tests\Support\InteractsWithAssetRegister;
use Tests\TestCase;

final class AssetPermissionTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_role_permission_catalog_exposes_all_three_asset_permissions(): void
    {
        $provider = new AssetRegisterPermissionProvider;

        self::assertSame('Tài sản', $provider->groupLabel());
        self::assertSame([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ], array_map(static fn ($permission): string => $permission->key, $provider->permissions()));
        self::assertSame(
            ['Tự phục vụ', 'Xem sổ tài sản', 'Quản lý sổ tài sản'],
            array_map(static fn ($permission): string => $permission->label, $provider->permissions()),
        );
        self::assertSame(
            [
                'Xem tài sản đang giữ, xác nhận bàn giao và báo sự cố tài sản của mình.',
                'Xem danh sách, chi tiết và lịch sử của mọi tài sản trong tài khoản.',
                'Quản lý tài sản và danh mục loại tài sản, nơi đặt; cần thêm quyền Xem sổ tài sản để thao tác.',
            ],
            array_map(static fn ($permission): string => $permission->description, $provider->permissions()),
        );

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

    public function test_role_permission_catalog_is_translated_in_english(): void
    {
        $originalLocale = app()->getLocale();
        app()->setLocale('en');

        try {
            $provider = new AssetRegisterPermissionProvider;
            self::assertSame('Assets', $provider->groupLabel());
            self::assertSame(
                ['Self-service', 'View asset register', 'Manage asset register'],
                array_map(static fn ($permission): string => $permission->label, $provider->permissions()),
            );
            self::assertSame(
                [
                    'View assets assigned to you, confirm handovers, and report issues with your assets.',
                    'View the list, details, and history of all assets in the account.',
                    'Manage assets and asset type and location catalogs; requires View asset register to make changes.',
                ],
                array_map(static fn ($permission): string => $permission->description, $provider->permissions()),
            );
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_asset_menu_redirects_to_the_first_allowed_page(): void
    {
        [$actor] = $this->actorWithPermissions([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);

        $module = new AssetRegisterModule;
        self::assertNotNull($module->sidebar());
        self::assertSame('asset-register.index', $module->sidebar()->routeName);
        self::assertSame(['vi' => 'Tài sản', 'en' => 'Assets'], $module->name());

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/mine');
    }

    public function test_default_member_can_only_open_my_assets_tab(): void
    {
        [$actor] = $this->actorWithPermissions([
            AccountUserPermissionEnum::VIEW->value,
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/mine');

        $this->actingAs($actor)
            ->get('/asset-register/mine')
            ->assertOk()
            ->assertSee('Của tôi')
            ->assertSee(__('asset-register::asset-register.mine.not_available_yet'))
            ->assertDontSee('Dữ liệu minh hoạ');

        $this->actingAs($actor)->get('/asset-register/registry')->assertNotFound();
        $this->actingAs($actor)->get('/asset-register/settings')->assertForbidden();
    }

    public function test_core_account_manager_can_open_settings_without_registry_routes(): void
    {
        [$actor] = $this->actorWithPermissions([
            AccountUserPermissionEnum::MANAGE->value,
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/mine');

        $this->actingAs($actor)->get('/asset-register/mine')->assertOk();
        $this->actingAs($actor)->get('/asset-register/settings')->assertOk();
    }

    public function test_asset_manager_sees_only_implemented_tabs(): void
    {
        [$actor] = $this->actorWithPermissions([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/mine');

        $this->actingAs($actor)
            ->get('/asset-register/mine')
            ->assertOk()
            ->assertSeeInOrder(['Của tôi', 'Cài đặt'])
            ->assertDontSee('Sổ tài sản');
    }

    public function test_registry_only_member_has_no_uc1_to_uc3_landing_page(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.assets.view']);

        $this->actingAs($actor)->get('/asset-register')->assertForbidden();
        $this->actingAs($actor)->get('/asset-register/registry')->assertNotFound();
    }

    public function test_uc4_to_uc7_routes_are_not_registered_in_this_pr(): void
    {
        [$actor] = $this->actorWithPermissions([
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);

        $this->actingAs($actor)->get('/asset-register/registry')->assertNotFound();
        $this->actingAs($actor)->get('/asset-register/registry/create')->assertNotFound();
        $this->actingAs($actor)->post('/asset-register/registry')->assertNotFound();
        $this->actingAs($actor)->get('/asset-register/assets/1')->assertNotFound();
        $this->actingAs($actor)->get('/asset-register/assets/1/edit')->assertNotFound();
        $this->actingAs($actor)->put('/asset-register/assets/1')->assertNotFound();
    }

    public function test_account_without_plugin_grant_is_blocked(): void
    {
        $this->requirePlugin(AssetRegisterModule::CODE);
        [$owner, $account] = $this->makeAccountWithFullAccessOwner('ungranted');
        $this->activateSubscriptionFor($account);
        [$actor] = $this->addMemberWithPermissions($account, ['asset-register.assets.manage'], 'ungranted actor');
        AccountPlugin::query()->where('plugin_code', AssetRegisterModule::CODE)->delete();
        app(PluginRegistry::class)->flushAccountCache((int) $account->id);
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

    public function test_asset_manage_permission_does_not_imply_registry_view(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.assets.manage']);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/settings');

        $this->actingAs($actor)->get('/asset-register/registry')->assertNotFound();
        $this->actingAs($actor)->get('/asset-register/settings')->assertOk()
            ->assertSee('Cài đặt')
            ->assertSee(__('asset-register::asset-register.settings.manage_requires_view'))
            ->assertDontSee('Sổ tài sản')
            ->assertDontSee('Thêm loại')
            ->assertDontSee('href="'.route('asset-register.mine').'"', false);
    }
}

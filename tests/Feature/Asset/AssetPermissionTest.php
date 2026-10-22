<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset;

require_once __DIR__.'/Concerns/InteractsWithAssetRegister.php';

use App\Enums\Account\AccountUserPermissionEnum;
use App\Models\AccountPlugin;
use App\Models\Plugin;
use App\Support\Account\AccountContext;
use App\Support\Rbac\AccountPermissionCatalogRegistry;
use Hki\AssetRegister\AssetRegisterModule;
use Hki\AssetRegister\Rbac\AssetRegisterPermissionProvider;
use Hki\AssetRegister\Tests\Feature\Asset\Concerns\InteractsWithAssetRegister;
use Tests\TestCase;

final class AssetPermissionTest extends TestCase
{
    use InteractsWithAssetRegister;

    public function test_danh_muc_vai_tro_hien_ba_quyen_thuoc_nhom_tai_san(): void
    {
        $provider = new AssetRegisterPermissionProvider;

        self::assertSame('Tài sản', $provider->groupLabel());
        self::assertSame([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ], array_map(static fn ($permission): string => $permission->key, $provider->permissions()));

        $keys = app(AccountPermissionCatalogRegistry::class)->knownPermissionKeys();
        self::assertContains('asset-register.self.use', $keys);
        self::assertContains('asset-register.assets.view', $keys);
        self::assertContains('asset-register.assets.manage', $keys);
    }

    public function test_muc_tai_san_mo_tab_dau_tien_duoc_phep(): void
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
            ->assertRedirect('/asset-register/registry');
    }

    public function test_thanh_vien_mac_dinh_chi_mo_duoc_tab_cua_toi(): void
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
            ->assertSee('Của tôi');

        $this->actingAs($actor)->get('/asset-register/registry')->assertForbidden();
        $this->actingAs($actor)->get('/asset-register/settings')->assertForbidden();
    }

    public function test_quyen_manage_mac_dinh_mo_so_va_cai_dat(): void
    {
        [$actor] = $this->actorWithPermissions([
            AccountUserPermissionEnum::MANAGE->value,
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/registry');

        $this->actingAs($actor)->get('/asset-register/registry')->assertOk();
        $this->actingAs($actor)->get('/asset-register/settings')->assertOk();
    }

    public function test_quan_tri_co_day_du_ba_tab(): void
    {
        [$actor] = $this->actorWithPermissions([
            'asset-register.self.use',
            'asset-register.assets.view',
            'asset-register.assets.manage',
        ]);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/registry');

        $this->actingAs($actor)
            ->get('/asset-register/registry')
            ->assertOk()
            ->assertSee('Của tôi')
            ->assertSee('Sổ tài sản')
            ->assertSee('Cài đặt');
    }

    public function test_tai_khoan_chua_duoc_cap_plugin_bi_chan(): void
    {
        $this->requirePlugin(AssetRegisterModule::CODE);
        [$owner, $account] = $this->makeAccountWithFullAccessOwner('ungranted');
        [$actor] = $this->addMemberWithPermissions($account, ['asset-register.assets.manage'], 'ungranted actor');
        AccountPlugin::query()->where('plugin_code', AssetRegisterModule::CODE)->delete();
        Plugin::query()->where('code', AssetRegisterModule::CODE)->delete();
        AccountContext::force((int) $account->id);

        $this->actingAs($actor)
            ->get('/asset-register/settings')
            ->assertForbidden();
    }

    public function test_thanh_vien_bi_dinh_chi_bi_chan(): void
    {
        [$actor, $account] = $this->actorWithPermissions([
            'asset-register.assets.manage',
        ]);
        $this->suspendMemberInAccount($actor, $account, 'Test');

        $this->actingAs($actor)
            ->get('/asset-register/settings')
            ->assertRedirect(route('accounts.index'));
    }

    public function test_quyen_quan_ly_khong_tu_dong_cap_quyen_xem_so(): void
    {
        [$actor] = $this->actorWithPermissions(['asset-register.assets.manage']);

        $this->actingAs($actor)
            ->get('/asset-register')
            ->assertRedirect('/asset-register/settings');

        $this->actingAs($actor)->get('/asset-register/registry')->assertForbidden();
        $this->actingAs($actor)->get('/asset-register/settings')->assertOk();
    }
}

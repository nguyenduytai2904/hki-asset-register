<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Feature\Asset\Concerns;

use App\Models\Account;
use App\Models\AccountPlugin;
use App\Models\Plugin;
use App\Models\User;
use App\Support\Account\AccountContext;
use App\Support\Plugin\PluginRegistry;
use Hki\AssetRegister\AssetRegisterModule;
use Tests\Support\AccountBillingFixture;

trait InteractsWithAssetRegister
{
    use AccountBillingFixture;

    private function actorWithPermissions(array $permissions, string $label = 'asset register'): array
    {
        $this->requirePlugin(AssetRegisterModule::CODE);
        [$owner, $account] = $this->makeAccountWithFullAccessOwner($label);
        [$actor] = $this->addMemberWithPermissions($account, $permissions, $label.' actor');

        $this->activatePluginFor($account, $owner);
        AccountContext::force((int) $account->id);

        return [$actor, $account];
    }

    private function activatePluginFor(Account $account, User $grantedBy): void
    {
        Plugin::query()->updateOrCreate(
            ['code' => AssetRegisterModule::CODE],
            [
                'name_vi' => 'Tài sản',
                'name_en' => 'Assets',
                'icon' => 'inventory_2',
                'version' => 'dev-main',
                'module_class' => AssetRegisterModule::class,
                'metadata_json' => (new AssetRegisterModule)->metadataJson(),
                'availability' => 'public',
                'status' => 'active',
                'registered_at' => now(),
            ],
        );

        AccountPlugin::query()->updateOrCreate(
            ['account_id' => $account->id, 'plugin_code' => AssetRegisterModule::CODE],
            ['granted_at' => now(), 'granted_by_user_id' => $grantedBy->id],
        );

        app(PluginRegistry::class)->flushAccountCache((int) $account->id);
    }

}

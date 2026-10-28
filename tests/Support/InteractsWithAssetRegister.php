<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Support;

use App\Models\Account;
use App\Models\AccountPlugin;
use App\Models\PlanPrice;
use App\Models\Plugin;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Account\AccountContext;
use App\Support\Edition\Edition;
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
        $this->activateSubscriptionFor($account);
        [$actor] = $this->addMemberWithPermissions($account, $permissions, $label.' actor');

        $this->activatePluginFor($account, $owner);
        AccountContext::force((int) $account->id);

        return [$actor, $account];
    }

    private function activateSubscriptionFor(Account $account): void
    {
        if (! Edition::billingEnabled()) {
            return;
        }

        $planPrice = PlanPrice::query()->firstOrFail();
        Subscription::query()->create([
            'account_id' => $account->id,
            'plan_price_id' => $planPrice->id,
            'status' => 'active',
            'current_period_end' => now()->addYear(),
        ]);
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

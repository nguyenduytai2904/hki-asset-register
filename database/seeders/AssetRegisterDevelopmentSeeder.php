<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Database\Seeders;

use App\Contracts\Services\AccountServices\AccountServiceInterface;
use App\Contracts\Services\EffectivePermissionServiceInterface;
use App\DTOs\Account\CreateAccountDTO;
use App\Models\Account;
use App\Models\AccountPlugin;
use App\Models\Plugin;
use App\Models\User;
use App\Support\Plugin\PluginRegistry;
use Hki\AssetRegister\AssetRegisterModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class AssetRegisterDevelopmentSeeder extends Seeder
{
    private const ACCOUNT_NAME = 'Asset Register Test Workspace';
    private const OWNER_EMAIL = 'asset-register-owner@sotaisan.test';
    private const DEMO_MEMBERS = [
        'ha' => 'Lê Thu Hà',
        'khoa' => 'Nguyễn Đăng Khoa',
        'bao' => 'Trần Minh Bảo',
        'an' => 'Phạm Hoàng An',
        'tam' => 'Vũ Ngọc Tâm',
        'huy' => 'Đỗ Quốc Huy',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $owner = User::query()->firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Asset Register Test Owner',
                'password' => Hash::make('asset-register-test'),
                'email_verified_at' => now(),
            ],
        );

        $account = Account::withoutGlobalScopes()
            ->where('name', self::ACCOUNT_NAME)
            ->first();

        $accountService = app(AccountServiceInterface::class);
        if ($account === null) {
            $accountDTO = $accountService->create(new CreateAccountDTO(
                name: self::ACCOUNT_NAME,
                owner_user_id: (int) $owner->id,
            ));
            $accountId = (int) $accountDTO->__get('id');
        } else {
            $accountId = (int) $account->id;
            $accountDTO = $accountService->findById($accountId);
            if ($accountDTO === null) {
                throw new RuntimeException("Could not load Asset Register test account {$accountId}.");
            }
            $accountService->addUserToAccount($accountDTO, (int) $owner->id);
        }

        $owner->forceFill(['current_account_id' => $accountId])->save();

        foreach (self::DEMO_MEMBERS as $key => $name) {
            $member = User::query()->firstOrCreate(
                ['email' => "{$key}@sotaisan.test"],
                [
                    'name' => $name,
                    'password' => Hash::make('asset-register-test'),
                    'email_verified_at' => now(),
                ],
            );

            $accountService->addUserToAccount($accountDTO, (int) $member->id);
        }

        $module = new AssetRegisterModule();
        Plugin::query()->updateOrCreate(
            ['code' => AssetRegisterModule::CODE],
            [
                'name_vi' => 'Tài sản',
                'name_en' => 'Assets',
                'icon' => $module->icon(),
                'version' => $module->version(),
                'module_class' => AssetRegisterModule::class,
                'metadata_json' => $module->metadataJson(),
                'availability' => 'public',
                'status' => 'active',
                'registered_at' => now(),
            ],
        );

        AccountPlugin::query()->updateOrCreate(
            ['account_id' => $accountId, 'plugin_code' => AssetRegisterModule::CODE],
            ['granted_at' => now(), 'granted_by_user_id' => $owner->id],
        );

        $pluginRegistry = app(PluginRegistry::class);
        $pluginRegistry->flushAccountCache($accountId);
        app(EffectivePermissionServiceInterface::class)
            ->syncActivePluginPermissionsToFullAccessRoles($accountId);

        $exitCode = Artisan::call('asset-register:demo-data', [
            'accountId' => $accountId,
            '--reset' => true,
        ]);
        if ($exitCode !== 0) {
            throw new RuntimeException('Asset Register development data could not be seeded: ' . Artisan::output());
        }
    }
}

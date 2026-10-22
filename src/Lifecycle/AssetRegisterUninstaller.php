<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Lifecycle;

use App\Models\Account;
use App\Models\User;
use App\Support\Plugin\Contracts\PluginUninstaller;
use App\Support\Plugin\DTOs\UninstallReport;
use App\Support\Plugin\DTOs\UninstallResult;
use Hki\AssetRegister\AssetRegisterModule;
use Illuminate\Support\Facades\DB;

final class AssetRegisterUninstaller implements PluginUninstaller
{
    public function uninstall(Account $account, User $actor, bool $hard = false, bool $dryRun = false): UninstallResult
    {
        $assetIds = DB::table('assets')->where('account_id', $account->id)->pluck('id');
        $rows = [
            'asset_histories' => DB::table('asset_histories')->whereIn('asset_id', $assetIds)->count(),
            'asset_handovers' => DB::table('asset_handovers')->whereIn('asset_id', $assetIds)->count(),
            'assets' => $assetIds->count(),
            'asset_types' => DB::table('asset_types')->where('account_id', $account->id)->count(),
            'asset_locations' => DB::table('asset_locations')->where('account_id', $account->id)->count(),
        ];

        if ($dryRun || !$hard) {
            return new UninstallResult(true, $rows, [], 0, null, '');
        }

        DB::table('asset_histories')->whereIn('asset_id', $assetIds)->delete();
        DB::table('asset_handovers')->whereIn('asset_id', $assetIds)->delete();
        DB::table('assets')->where('account_id', $account->id)->delete();
        DB::table('asset_types')->where('account_id', $account->id)->delete();
        DB::table('asset_locations')->where('account_id', $account->id)->delete();

        return new UninstallResult(true, $rows, [], 0, null, '');
    }

    public function preview(Account $account): UninstallReport
    {
        $assetIds = DB::table('assets')->where('account_id', $account->id)->pluck('id');

        return new UninstallReport(
            (string) $account->id,
            AssetRegisterModule::CODE,
            [
                'asset_histories' => DB::table('asset_histories')->whereIn('asset_id', $assetIds)->count(),
                'asset_handovers' => DB::table('asset_handovers')->whereIn('asset_id', $assetIds)->count(),
                'assets' => $assetIds->count(),
                'asset_types' => DB::table('asset_types')->where('account_id', $account->id)->count(),
                'asset_locations' => DB::table('asset_locations')->where('account_id', $account->id)->count(),
            ],
            [],
            [],
            ['count' => 0, 'size_bytes' => 0],
            [],
            [],
            [],
            '0 seconds',
        );
    }
}

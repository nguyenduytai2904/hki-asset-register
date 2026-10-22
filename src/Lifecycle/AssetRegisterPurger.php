<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Lifecycle;

use App\Models\User;
use App\Support\Plugin\Contracts\PluginPurger;
use App\Support\Plugin\DTOs\PurgeResult;
use Illuminate\Support\Facades\Schema;
use Hki\AssetRegister\AssetRegisterModule;

final class AssetRegisterPurger implements PluginPurger
{
    public function purge(User $actor, bool $dryRun = false): PurgeResult
    {
        $tables = AssetRegisterModule::TABLES;
        if (!$dryRun) {
            foreach (array_reverse($tables) as $table) {
                Schema::dropIfExists($table);
            }
        }

        return new PurgeResult(true, $dryRun ? [] : $tables, '');
    }
}

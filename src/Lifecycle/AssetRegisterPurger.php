<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Lifecycle;

use App\Models\User;
use App\Support\Plugin\Contracts\PluginPurger;
use App\Support\Plugin\DTOs\PurgeResult;
use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;
use Illuminate\Support\Facades\Schema;

final class AssetRegisterPurger implements PluginPurger
{
    public function purge(User $actor, bool $dryRun = false): PurgeResult
    {
        $tables = AssetRegisterSchema::dropOrder();
        if (! $dryRun) {
            foreach ($tables as $table) {
                Schema::dropIfExists($table);
            }
        }

        return new PurgeResult(true, $dryRun ? [] : $tables, '');
    }
}

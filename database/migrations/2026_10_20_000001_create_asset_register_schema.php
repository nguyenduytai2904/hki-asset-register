<?php

declare(strict_types=1);

use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TABLES = [
        'asset_types',
        'asset_locations',
        'assets',
        'asset_handovers',
        'asset_histories',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_TABLES as $table) {
            if (Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Legacy Asset Register table {$table} detected. Back up and migrate its data before applying the baseline migration.",
                );
            }
        }

        AssetRegisterSchema::createMissing();
    }

    public function down(): void
    {
        foreach (AssetRegisterSchema::dropOrder() as $table) {
            Schema::dropIfExists($table);
        }
    }
};

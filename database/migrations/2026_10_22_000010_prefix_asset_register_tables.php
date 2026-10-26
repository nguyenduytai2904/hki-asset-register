<?php

declare(strict_types=1);

use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TABLES = [
        'asset_types' => 'asset_register_types',
        'asset_locations' => 'asset_register_locations',
        'assets' => 'asset_register_assets',
        'asset_handovers' => 'asset_register_handovers',
        'asset_histories' => 'asset_register_histories',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_TABLES as $legacy => $current) {
            if (Schema::hasTable($legacy) && Schema::hasTable($current)) {
                throw new RuntimeException("Both legacy and prefixed Asset Register tables exist: {$legacy}, {$current}.");
            }

            if (Schema::hasTable($legacy)) {
                Schema::rename($legacy, $current);
            }
        }

        AssetRegisterSchema::createMissing();

        foreach (['asset_register_handovers', 'asset_register_histories'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'account_id')) {
                Schema::table($tableName, static function (Blueprint $table): void {
                    AssetRegisterSchema::accountIdColumn($table, nullable: true);
                });
            }

            DB::table($tableName)
                ->join('asset_register_assets', 'asset_register_assets.id', '=', "{$tableName}.asset_id")
                ->whereNull("{$tableName}.account_id")
                ->update(["{$tableName}.account_id" => DB::raw('asset_register_assets.account_id')]);

            if (DB::table($tableName)->whereNull('account_id')->exists()) {
                throw new RuntimeException("Could not determine account ownership for rows in {$tableName}.");
            }

            $accountIdColumn = collect(Schema::getColumns($tableName))
                ->first(static fn (array $column): bool => $column['name'] === 'account_id');
            $accountIdForeignKey = collect(Schema::getForeignKeys($tableName))
                ->first(static fn (array $foreignKey): bool => $foreignKey['columns'] === ['account_id']);
            $accountIdIsNullable = $accountIdColumn['nullable'] ?? false;

            if ($accountIdIsNullable || $accountIdForeignKey === null) {
                Schema::table($tableName, static function (Blueprint $table) use ($tableName, $accountIdIsNullable, $accountIdForeignKey): void {
                    if ($accountIdIsNullable) {
                        AssetRegisterSchema::makeAccountIdRequired($table);
                    }
                    if ($accountIdForeignKey === null) {
                        AssetRegisterSchema::accountForeignKey($table, $tableName);
                    }
                });
            }
        }
    }

    public function down(): void {}
};

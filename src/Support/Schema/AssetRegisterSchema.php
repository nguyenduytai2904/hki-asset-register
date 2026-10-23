<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Support\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical schema for Asset Register. Migrations and install lifecycle both use this class.
 */
final class AssetRegisterSchema
{
    public const TABLE_ORDER = [
        'asset_register_types',
        'asset_register_locations',
        'asset_register_assets',
        'asset_register_handovers',
        'asset_register_histories',
    ];

    /** @return list<string> */
    public static function tables(): array
    {
        return self::TABLE_ORDER;
    }

    /** @return list<string> */
    public static function dropOrder(): array
    {
        return array_reverse(self::TABLE_ORDER);
    }

    /** @return list<string> */
    public static function createMissing(): array
    {
        $created = [];

        foreach (self::TABLE_ORDER as $table) {
            if (Schema::hasTable($table)) {
                continue;
            }

            self::create($table);
            $created[] = $table;
        }

        return $created;
    }

    public static function create(string $table): void
    {
        match ($table) {
            'asset_register_types' => self::createTypes(),
            'asset_register_locations' => self::createLocations(),
            'asset_register_assets' => self::createAssets(),
            'asset_register_handovers' => self::createHandovers(),
            'asset_register_histories' => self::createHistories(),
            default => throw new \InvalidArgumentException("Table {$table} is not owned by Asset Register."),
        };
    }

    public static function accountScope(Blueprint $table, string $tableName, bool $nullable = false): void
    {
        self::accountIdColumn($table, $nullable);
        self::accountForeignKey($table, $tableName);
    }

    public static function accountIdColumn(Blueprint $table, bool $nullable = false): void
    {
        $table->unsignedBigInteger('account_id')->nullable($nullable);
    }

    public static function makeAccountIdRequired(Blueprint $table): void
    {
        $table->unsignedBigInteger('account_id')->nullable(false)->change();
    }

    public static function accountForeignKey(Blueprint $table, string $tableName): void
    {
        $table->foreign('account_id', "{$tableName}_account_id_foreign")
            ->references('id')
            ->on('accounts')
            ->cascadeOnDelete();
    }

    private static function createTypes(): void
    {
        Schema::create('asset_register_types', static function (Blueprint $table): void {
            $table->id();
            self::accountScope($table, 'asset_register_types');
            $table->string('name', 100)->collation('utf8mb4_0900_as_ci');
            $table->string('prefix', 6);
            $table->string('icon', 50)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->unsignedInteger('next_code_number')->default(1);
            $table->timestamps();
            $table->unique(['account_id', 'prefix'], 'asset_register_types_account_id_prefix_unique');
            $table->unique(['account_id', 'name'], 'asset_register_types_account_id_name_unique');
        });
    }

    private static function createLocations(): void
    {
        Schema::create('asset_register_locations', static function (Blueprint $table): void {
            $table->id();
            self::accountScope($table, 'asset_register_locations');
            $table->string('name', 150)->collation('utf8mb4_0900_as_ci');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index('account_id', 'asset_register_locations_account_id_index');
            $table->unique(['account_id', 'name'], 'asset_register_locations_account_id_name_unique');
        });
    }

    private static function createAssets(): void
    {
        Schema::create('asset_register_assets', static function (Blueprint $table): void {
            $table->id();
            self::accountScope($table, 'asset_register_assets');
            $table->foreignId('asset_type_id')->constrained('asset_register_types')->restrictOnDelete();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_register_locations')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial', 100)->nullable();
            $table->string('condition', 20);
            $table->string('status', 30);
            $table->unsignedBigInteger('current_keeper_id')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('configuration')->nullable();
            $table->string('supplier', 150)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'code'], 'asset_register_assets_account_id_code_unique');
            $table->index('current_keeper_id', 'asset_register_assets_current_keeper_id_index');
            $table->index(['account_id', 'status'], 'asset_register_assets_account_id_status_index');
        });
    }

    private static function createHandovers(): void
    {
        Schema::create('asset_register_handovers', static function (Blueprint $table): void {
            $table->id();
            self::accountScope($table, 'asset_register_handovers');
            $table->foreignId('asset_id')->constrained('asset_register_assets')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30);
            $table->string('condition_on_handover', 20)->nullable();
            $table->string('condition_on_return', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'asset_id'], 'asset_register_handovers_account_id_asset_id_index');
            $table->index(['account_id', 'recipient_id'], 'asset_register_handovers_account_id_recipient_id_index');
        });
    }

    private static function createHistories(): void
    {
        Schema::create('asset_register_histories', static function (Blueprint $table): void {
            $table->id();
            self::accountScope($table, 'asset_register_histories');
            $table->foreignId('asset_id')->constrained('asset_register_assets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index(['account_id', 'asset_id'], 'asset_register_histories_account_id_asset_id_index');
            $table->index(['account_id', 'created_at'], 'asset_register_histories_account_id_created_at_index');
        });
    }
}

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'asset_register_assets';

    private const ACCOUNT_KEEPER_INDEX = 'asset_register_assets_account_id_current_keeper_id_index';

    private const LEGACY_KEEPER_INDEX = 'asset_register_assets_current_keeper_id_index';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (! Schema::hasIndex(self::TABLE, self::ACCOUNT_KEEPER_INDEX)) {
            Schema::table(self::TABLE, static function (Blueprint $table): void {
                $table->index(['account_id', 'current_keeper_id'], self::ACCOUNT_KEEPER_INDEX);
            });
        }

        if (Schema::hasIndex(self::TABLE, self::LEGACY_KEEPER_INDEX)) {
            Schema::table(self::TABLE, static function (Blueprint $table): void {
                $table->dropIndex(self::LEGACY_KEEPER_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (Schema::hasIndex(self::TABLE, self::ACCOUNT_KEEPER_INDEX)) {
            Schema::table(self::TABLE, static function (Blueprint $table): void {
                $table->dropIndex(self::ACCOUNT_KEEPER_INDEX);
            });
        }

        if (! Schema::hasIndex(self::TABLE, self::LEGACY_KEEPER_INDEX)) {
            Schema::table(self::TABLE, static function (Blueprint $table): void {
                $table->index('current_keeper_id', self::LEGACY_KEEPER_INDEX);
            });
        }
    }
};

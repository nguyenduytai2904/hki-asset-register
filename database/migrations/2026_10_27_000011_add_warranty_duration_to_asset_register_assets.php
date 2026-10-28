<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_register_assets') && ! Schema::hasColumn('asset_register_assets', 'warranty_duration_months')) {
            Schema::table('asset_register_assets', static function (Blueprint $table): void {
                $table->unsignedSmallInteger('warranty_duration_months')->nullable()->after('warranty_end_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('asset_register_assets') && Schema::hasColumn('asset_register_assets', 'warranty_duration_months')) {
            Schema::table('asset_register_assets', static function (Blueprint $table): void {
                $table->dropColumn('warranty_duration_months');
            });
        }
    }
};

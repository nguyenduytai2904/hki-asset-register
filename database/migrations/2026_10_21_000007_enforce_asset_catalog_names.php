<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_types', function (Blueprint $table): void {
            $table->string('name', 100)->collation('utf8mb4_0900_as_ci')->change();
            $table->unique(['account_id', 'name']);
        });

        Schema::table('asset_locations', function (Blueprint $table): void {
            $table->index('account_id');
            $table->dropUnique('asset_locations_account_id_name_unique');
            $table->string('name', 150)->collation('utf8mb4_0900_as_ci')->change();
            $table->unique(['account_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table): void {
            $table->dropUnique('asset_types_account_id_name_unique');
            $table->string('name', 100)->collation('utf8mb4_unicode_ci')->change();
        });

        Schema::table('asset_locations', function (Blueprint $table): void {
            $table->dropUnique('asset_locations_account_id_name_unique');
            $table->string('name', 150)->collation('utf8mb4_unicode_ci')->change();
            $table->unique(['account_id', 'name']);
            $table->dropIndex('asset_locations_account_id_index');
        });
    }
};

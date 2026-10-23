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
            $table->unsignedInteger('next_code_number')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table): void {
            $table->dropColumn('next_code_number');
        });
    }
};

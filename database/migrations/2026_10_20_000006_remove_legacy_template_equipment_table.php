<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('template_equipment');
    }

    public function down(): void
    {
        Schema::create('template_equipment', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('asset_tag', 80);
            $table->string('name', 160);
            $table->string('category', 80);
            $table->string('status', 30)->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['account_id', 'asset_tag']);
        });
    }
};

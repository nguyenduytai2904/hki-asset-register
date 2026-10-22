<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150); // Tên nơi đặt 1-150 ký tự (BR-03)
            $table->text('description')->nullable(); // Mô tả tối đa 500 ký tự (BR-03)
            $table->string('status', 20)->default('active'); // Đang dùng / Ngừng dùng
            $table->timestamps();

            // Tên nơi đặt duy nhất trong tài khoản (BR-03)
            $table->unique(['account_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_locations');
    }
};

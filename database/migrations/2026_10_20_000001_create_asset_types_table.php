<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100); // Tên loại 1-100 ký tự (BR-02)
            $table->string('prefix', 6); // Tiền tố 2-6 ký tự (BR-02)
            $table->string('icon', 50)->nullable();
            $table->string('status', 20)->default('active'); // Đang dùng hoặc Ngừng dùng
            $table->text('description')->nullable(); // Mô tả tối đa 500 ký tự (BR-02)
            $table->timestamps();

            // Tiền tố mã duy nhất trong tài khoản (BR-02)
            $table->unique(['account_id', 'prefix']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_types');
    }
};

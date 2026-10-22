<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_type_id')->constrained('asset_types')->restrictOnDelete();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();

            // Thông tin cơ bản
            $table->string('code', 30); // Mã tài sản 2-30 ký tự (BR-01)
            $table->string('name', 150); // Giới hạn 1-150 ký tự (UC-005)
            $table->string('brand', 100)->nullable(); // Hãng
            $table->string('model', 100)->nullable(); // Model
            $table->string('serial', 100)->nullable(); // Số sê-ri
            $table->string('condition', 20); // Tình trạng: new, good, fair, poor
            $table->string('status', 30); // Trạng thái: available, in_use, in_repair, broken, lost, disposed (BR-05)

            // Người giữ hiện tại (nếu có)
            $table->unsignedBigInteger('current_keeper_id')->nullable()->index();

            // Thông tin mua sắm & bổ sung (UC-005)
            $table->date('warranty_end_date')->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('configuration')->nullable(); // Cấu hình (tối đa 2000 ký tự)
            $table->string('supplier', 150)->nullable(); // Nhà cung cấp
            $table->string('receipt_number', 100)->nullable(); // Số chứng từ
            $table->text('note')->nullable(); // Ghi chú

            $table->timestamps();

            // Mã tài sản phải là duy nhất trong cùng 1 tài khoản (BR-01)
            $table->unique(['account_id', 'code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('assets');
    }
};

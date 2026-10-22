<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_handovers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete(); // Người nhận tài sản
            $table->string('status', 30); // pending, confirmed, closed (BR-06, BR-08)
            $table->string('condition_on_handover', 20)->nullable(); // Tình trạng lúc giao
            $table->string('condition_on_return', 20)->nullable(); // Tình trạng lúc thu hồi
            $table->text('note')->nullable(); // Ghi chú khi giao
            
            // Thời gian
            $table->timestamp('handed_over_at')->nullable(); // Thời điểm giao
            $table->timestamp('confirmed_at')->nullable(); // Thời điểm xác nhận
            $table->timestamp('closed_at')->nullable(); // Thời điểm đóng lần giao
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_handovers');
    }
};

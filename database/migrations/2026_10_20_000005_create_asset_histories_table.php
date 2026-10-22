<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Người thực hiện thao tác
            $table->string('action', 50); // Loại thao tác: added, handed_over, status_changed, etc.
            $table->text('description')->nullable(); // Chi tiết thao tác (BR-12)
            
            // Lịch sử không cho phép sửa/xóa nên có thể chỉ dùng created_at theo BR-12
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_histories');
    }
};

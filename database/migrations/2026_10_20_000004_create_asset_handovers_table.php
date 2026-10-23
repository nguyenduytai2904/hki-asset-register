<?php

declare(strict_types=1);

use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AssetRegisterSchema::createMissing();
    }

    public function down(): void {}
};

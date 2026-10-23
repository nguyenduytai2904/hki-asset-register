<?php

declare(strict_types=1);

use Hki\AssetRegister\Support\Schema\AssetRegisterSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        AssetRegisterSchema::createMissing();
    }

    public function down() {}
};

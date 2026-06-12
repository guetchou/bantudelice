<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasTable('users')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN type ENUM('user','admin','restaurant','driver') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasTable('users')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN type ENUM('user','admin','restaurant') NOT NULL DEFAULT 'user'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_chats', function (Blueprint $table) {
            if (!Schema::hasColumn('order_chats', 'admin_last_read_at')) {
                $table->timestamp('admin_last_read_at')->nullable()->after('driver_last_read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_chats', function (Blueprint $table) {
            if (Schema::hasColumn('order_chats', 'admin_last_read_at')) {
                $table->dropColumn('admin_last_read_at');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReadAtToNotificationLogsTable extends Migration
{
    public function up()
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_logs', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('context');
            }
        });
    }

    public function down()
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            if (Schema::hasColumn('notification_logs', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });
    }
}

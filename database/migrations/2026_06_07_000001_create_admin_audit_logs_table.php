<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminAuditLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('admin_audit_logs')) return;

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_email', 191)->nullable();
            $table->string('method', 10);
            $table->string('path', 500);
            $table->string('route_name', 191)->nullable()->index();
            $table->string('action', 191)->nullable()->index();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_audit_logs');
    }
}

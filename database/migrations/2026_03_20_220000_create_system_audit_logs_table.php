<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemAuditLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('system_audit_logs')) {
            return;
        }

        Schema::create('system_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('actor_type', 40)->default('system')->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('target_type', 80)->nullable()->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->string('target_ref', 120)->nullable()->index();
            $table->string('action', 120)->index();
            $table->string('status', 60)->nullable()->index();
            $table->json('meta')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_audit_logs');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('notification_logs')) {
            return;
        }

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('channel', 30)->index();
            $table->string('recipient_type', 40)->nullable()->index();
            $table->unsignedBigInteger('recipient_id')->nullable()->index();
            $table->string('recipient_address')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('provider', 60)->nullable()->index();
            $table->string('status', 40)->default('pending')->index();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
}

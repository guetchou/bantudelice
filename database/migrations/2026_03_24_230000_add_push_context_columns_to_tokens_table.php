<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPushContextColumnsToTokensTable extends Migration
{
    public function up()
    {
        Schema::table('user_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('user_tokens', 'platform')) {
                $table->string('platform', 32)->nullable()->after('device_tokens');
            }
            if (!Schema::hasColumn('user_tokens', 'locale')) {
                $table->string('locale', 10)->default('fr')->after('platform');
            }
            if (!Schema::hasColumn('user_tokens', 'site_key')) {
                $table->string('site_key', 64)->nullable()->after('locale');
            }
            if (!Schema::hasColumn('user_tokens', 'active')) {
                $table->boolean('active')->default(true)->after('site_key');
            }
            if (!Schema::hasColumn('user_tokens', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('active');
            }
            if (!Schema::hasColumn('user_tokens', 'metadata')) {
                $table->json('metadata')->nullable()->after('last_seen_at');
            }
        });

        Schema::table('driver_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_tokens', 'platform')) {
                $table->string('platform', 32)->nullable()->after('device_tokens');
            }
            if (!Schema::hasColumn('driver_tokens', 'locale')) {
                $table->string('locale', 10)->default('fr')->after('platform');
            }
            if (!Schema::hasColumn('driver_tokens', 'site_key')) {
                $table->string('site_key', 64)->nullable()->after('locale');
            }
            if (!Schema::hasColumn('driver_tokens', 'active')) {
                $table->boolean('active')->default(true)->after('site_key');
            }
            if (!Schema::hasColumn('driver_tokens', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('active');
            }
            if (!Schema::hasColumn('driver_tokens', 'metadata')) {
                $table->json('metadata')->nullable()->after('last_seen_at');
            }
        });
    }

    public function down()
    {
        Schema::table('user_tokens', function (Blueprint $table) {
            foreach (['platform', 'locale', 'site_key', 'active', 'last_seen_at', 'metadata'] as $column) {
                if (Schema::hasColumn('user_tokens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('driver_tokens', function (Blueprint $table) {
            foreach (['platform', 'locale', 'site_key', 'active', 'last_seen_at', 'metadata'] as $column) {
                if (Schema::hasColumn('driver_tokens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialAuthColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'social_provider')) {
                $table->string('social_provider', 32)->nullable()->after('type');
            }
            if (!Schema::hasColumn('users', 'social_id')) {
                $table->string('social_id', 191)->nullable()->after('social_provider');
            }
            if (!Schema::hasColumn('users', 'social_avatar')) {
                $table->text('social_avatar')->nullable()->after('social_id');
            }
            if (!Schema::hasColumn('users', 'api_token')) {
                // hash('sha256', $token) => 64 chars
                $table->string('api_token', 64)->nullable()->after('social_avatar');
            }

            // Indexes
            $table->index(['social_provider', 'social_id'], 'users_social_provider_id_index');
            $table->index(['api_token'], 'users_api_token_index');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'api_token')) {
                $table->dropIndex('users_api_token_index');
            }
            if (Schema::hasColumn('users', 'social_provider') || Schema::hasColumn('users', 'social_id')) {
                $table->dropIndex('users_social_provider_id_index');
            }

            $cols = [];
            foreach (['email_verified_at', 'social_provider', 'social_id', 'social_avatar', 'api_token'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $cols[] = $c;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
}



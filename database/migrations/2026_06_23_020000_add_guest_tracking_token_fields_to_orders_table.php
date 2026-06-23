<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuestTrackingTokenFieldsToOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'tracking_token_hash')) {
                $table->string('tracking_token_hash', 64)->nullable()->index()->after('cash_collection_reference');
            }

            if (! Schema::hasColumn('orders', 'tracking_token_expires_at')) {
                $table->timestamp('tracking_token_expires_at')->nullable()->index()->after('tracking_token_hash');
            }

            if (! Schema::hasColumn('orders', 'tracking_token_last_used_at')) {
                $table->timestamp('tracking_token_last_used_at')->nullable()->after('tracking_token_expires_at');
            }

            if (! Schema::hasColumn('orders', 'tracking_token_revoked_at')) {
                $table->timestamp('tracking_token_revoked_at')->nullable()->index()->after('tracking_token_last_used_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'tracking_token_hash')) {
                $table->dropIndex(['tracking_token_hash']);
                $table->dropColumn('tracking_token_hash');
            }

            if (Schema::hasColumn('orders', 'tracking_token_expires_at')) {
                $table->dropIndex(['tracking_token_expires_at']);
                $table->dropColumn('tracking_token_expires_at');
            }

            if (Schema::hasColumn('orders', 'tracking_token_last_used_at')) {
                $table->dropColumn('tracking_token_last_used_at');
            }

            if (Schema::hasColumn('orders', 'tracking_token_revoked_at')) {
                $table->dropIndex(['tracking_token_revoked_at']);
                $table->dropColumn('tracking_token_revoked_at');
            }
        });
    }
}

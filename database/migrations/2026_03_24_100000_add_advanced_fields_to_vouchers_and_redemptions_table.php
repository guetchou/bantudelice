<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdvancedFieldsToVouchersAndRedemptionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (!Schema::hasColumn('vouchers', 'discount_type')) {
                    $table->string('discount_type', 30)->default('percentage')->after('discount');
                }
                if (!Schema::hasColumn('vouchers', 'discount_value')) {
                    $table->decimal('discount_value', 14, 2)->nullable()->after('discount_type');
                }
                if (!Schema::hasColumn('vouchers', 'min_order_amount')) {
                    $table->decimal('min_order_amount', 14, 2)->default(0)->after('discount_value');
                }
                if (!Schema::hasColumn('vouchers', 'max_discount_amount')) {
                    $table->decimal('max_discount_amount', 14, 2)->nullable()->after('min_order_amount');
                }
                if (!Schema::hasColumn('vouchers', 'usage_limit')) {
                    $table->unsignedInteger('usage_limit')->nullable()->after('max_discount_amount');
                }
                if (!Schema::hasColumn('vouchers', 'used_count')) {
                    $table->unsignedInteger('used_count')->default(0)->after('usage_limit');
                }
                if (!Schema::hasColumn('vouchers', 'per_user_limit')) {
                    $table->unsignedInteger('per_user_limit')->default(1)->after('used_count');
                }
                if (!Schema::hasColumn('vouchers', 'stackable')) {
                    $table->boolean('stackable')->default(false)->after('per_user_limit');
                }
                if (!Schema::hasColumn('vouchers', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('stackable');
                }
                if (!Schema::hasColumn('vouchers', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable()->after('is_active');
                }
                if (!Schema::hasColumn('vouchers', 'ends_at')) {
                    $table->timestamp('ends_at')->nullable()->after('starts_at');
                }
                if (!Schema::hasColumn('vouchers', 'rules')) {
                    $table->json('rules')->nullable()->after('ends_at');
                }
            });
        }

        if (Schema::hasTable('voucher_redemptions')) {
            Schema::table('voucher_redemptions', function (Blueprint $table) {
                if (!Schema::hasColumn('voucher_redemptions', 'discount_type')) {
                    $table->string('discount_type', 30)->nullable()->after('discount_amount');
                }
                if (!Schema::hasColumn('voucher_redemptions', 'discount_rate')) {
                    $table->decimal('discount_rate', 14, 2)->nullable()->after('discount_type');
                }
                if (!Schema::hasColumn('voucher_redemptions', 'discount_cap')) {
                    $table->decimal('discount_cap', 14, 2)->nullable()->after('discount_rate');
                }
                if (!Schema::hasColumn('voucher_redemptions', 'idempotency_key')) {
                    $table->string('idempotency_key', 120)->nullable()->index()->after('order_no');
                }
                if (!Schema::hasColumn('voucher_redemptions', 'details')) {
                    $table->json('details')->nullable()->after('released_at');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('voucher_redemptions')) {
            Schema::table('voucher_redemptions', function (Blueprint $table) {
                if (Schema::hasColumn('voucher_redemptions', 'details')) {
                    $table->dropColumn('details');
                }
                if (Schema::hasColumn('voucher_redemptions', 'idempotency_key')) {
                    $table->dropColumn('idempotency_key');
                }
                if (Schema::hasColumn('voucher_redemptions', 'discount_cap')) {
                    $table->dropColumn('discount_cap');
                }
                if (Schema::hasColumn('voucher_redemptions', 'discount_rate')) {
                    $table->dropColumn('discount_rate');
                }
                if (Schema::hasColumn('voucher_redemptions', 'discount_type')) {
                    $table->dropColumn('discount_type');
                }
            });
        }

        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                foreach (['rules', 'ends_at', 'starts_at', 'is_active', 'stackable', 'per_user_limit', 'used_count', 'usage_limit', 'max_discount_amount', 'min_order_amount', 'discount_value', 'discount_type'] as $column) {
                    if (Schema::hasColumn('vouchers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('driver_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedTinyInteger('rating');
                $table->text('reviews')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('reviews', 'order_id')) {
                    $table->unsignedBigInteger('order_id')->nullable()->after('user_id');
                }
            });
        }

        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('drivers', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->after('avg_rating');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'order_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('order_id');
            });
        }

        Schema::table('drivers', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('drivers', 'avg_rating')) {
                $drops[] = 'avg_rating';
            }
            if (Schema::hasColumn('drivers', 'rating_count')) {
                $drops[] = 'rating_count';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};

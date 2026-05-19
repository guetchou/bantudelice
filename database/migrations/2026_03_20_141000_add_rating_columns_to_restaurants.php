<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->default(0)->after('featured');
            }
            if (!Schema::hasColumn('restaurants', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->after('avg_rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('restaurants', 'avg_rating')) {
                $drops[] = 'avg_rating';
            }
            if (Schema::hasColumn('restaurants', 'rating_count')) {
                $drops[] = 'rating_count';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};

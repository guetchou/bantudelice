<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultToUserAddressTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_address') || Schema::hasColumn('user_address', 'is_default')) {
            return;
        }

        Schema::table('user_address', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('complete_address');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('user_address') || !Schema::hasColumn('user_address', 'is_default')) {
            return;
        }

        Schema::table('user_address', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}

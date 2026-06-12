<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTwoFactorColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('users', 'two_factor_secret'))  { $drops[] = 'two_factor_secret'; }
            if (Schema::hasColumn('users', 'two_factor_enabled'))  { $drops[] = 'two_factor_enabled'; }
            if ($drops) { $table->dropColumn($drops); }
        });
    }
}

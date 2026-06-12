<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPasswordRotationFieldsToDriversTable extends Migration
{
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'password_must_change')) {
                $table->boolean('password_must_change')->default(false)->after('approved');
            }

            if (!Schema::hasColumn('drivers', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password_must_change');
            }

            if (!Schema::hasColumn('drivers', 'password_temp_issued_at')) {
                $table->timestamp('password_temp_issued_at')->nullable()->after('password_changed_at');
            }

            if (!Schema::hasColumn('drivers', 'provisioned_by_admin_id')) {
                $table->unsignedBigInteger('provisioned_by_admin_id')->nullable()->after('password_temp_issued_at');
            }
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $drops = [];

            foreach (['password_must_change', 'password_changed_at', 'password_temp_issued_at', 'provisioned_by_admin_id'] as $column) {
                if (Schema::hasColumn('drivers', $column)) {
                    $drops[] = $column;
                }
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
}

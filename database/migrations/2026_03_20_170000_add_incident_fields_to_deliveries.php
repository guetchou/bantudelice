<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIncidentFieldsToDeliveries extends Migration
{
    public function up()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'incident_status')) {
                $table->string('incident_status')->nullable()->after('cash_collected_at');
            }
            if (!Schema::hasColumn('deliveries', 'incident_reason')) {
                $table->string('incident_reason')->nullable()->after('incident_status');
            }
            if (!Schema::hasColumn('deliveries', 'incident_notes')) {
                $table->text('incident_notes')->nullable()->after('incident_reason');
            }
            if (!Schema::hasColumn('deliveries', 'incident_reported_by')) {
                $table->string('incident_reported_by')->nullable()->after('incident_notes');
            }
            if (!Schema::hasColumn('deliveries', 'incident_reported_by_id')) {
                $table->unsignedBigInteger('incident_reported_by_id')->nullable()->after('incident_reported_by');
            }
            if (!Schema::hasColumn('deliveries', 'incident_reported_at')) {
                $table->timestamp('incident_reported_at')->nullable()->after('incident_reported_by_id');
            }
            if (!Schema::hasColumn('deliveries', 'failed_attempts')) {
                $table->unsignedInteger('failed_attempts')->default(0)->after('incident_reported_at');
            }
            if (!Schema::hasColumn('deliveries', 'last_failed_attempt_at')) {
                $table->timestamp('last_failed_attempt_at')->nullable()->after('failed_attempts');
            }
            if (!Schema::hasColumn('deliveries', 'customer_absent_at')) {
                $table->timestamp('customer_absent_at')->nullable()->after('last_failed_attempt_at');
            }
            if (!Schema::hasColumn('deliveries', 'redelivery_requested_at')) {
                $table->timestamp('redelivery_requested_at')->nullable()->after('customer_absent_at');
            }
            if (!Schema::hasColumn('deliveries', 'support_status')) {
                $table->string('support_status')->nullable()->after('redelivery_requested_at');
            }
            if (!Schema::hasColumn('deliveries', 'support_notes')) {
                $table->text('support_notes')->nullable()->after('support_status');
            }
            if (!Schema::hasColumn('deliveries', 'support_resolved_at')) {
                $table->timestamp('support_resolved_at')->nullable()->after('support_notes');
            }
            if (!Schema::hasColumn('deliveries', 'support_resolved_by')) {
                $table->unsignedBigInteger('support_resolved_by')->nullable()->after('support_resolved_at');
            }
        });
    }

    public function down()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            foreach ([
                'incident_status',
                'incident_reason',
                'incident_notes',
                'incident_reported_by',
                'incident_reported_by_id',
                'incident_reported_at',
                'failed_attempts',
                'last_failed_attempt_at',
                'customer_absent_at',
                'redelivery_requested_at',
                'support_status',
                'support_notes',
                'support_resolved_at',
                'support_resolved_by',
            ] as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

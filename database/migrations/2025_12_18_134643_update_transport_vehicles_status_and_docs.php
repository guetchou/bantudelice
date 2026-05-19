<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transport_vehicles', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->text('rejection_reason')->nullable()->after('status');
            $table->json('documents')->nullable()->after('features');
            $table->dateTime('approved_at')->nullable()->after('rejection_reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transport_vehicles', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
            $table->dropColumn(['rejection_reason', 'documents', 'approved_at', 'approved_by']);
        });
    }
};

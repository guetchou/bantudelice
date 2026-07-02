<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_merchants', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('name');
            $table->string('slug', 128)->unique();
            $table->char('country', 2)->default('CG');
            $table->string('email', 191)->unique();
            $table->string('status', 32)->default('pending')->index();
            // portal_client_id added in migration 9 (circular FK resolved last)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_merchants');
    }
};

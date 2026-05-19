<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shipment_audit_logs', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // L'acteur (Admin/Système)
            $blueprint->string('event'); // ex: status_override, assigned_courier
            $blueprint->json('old_values')->nullable();
            $blueprint->json('new_values')->nullable();
            $blueprint->string('ip_address')->nullable();
            $blueprint->string('user_agent')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipment_audit_logs');
    }
};


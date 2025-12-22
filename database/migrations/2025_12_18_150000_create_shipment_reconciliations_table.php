<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shipment_reconciliations', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('courier_id')->constrained('drivers');
            $blueprint->foreignId('admin_id')->constrained('users');
            $blueprint->decimal('amount_collected', 15, 2);
            $blueprint->decimal('amount_reconciled', 15, 2);
            $blueprint->json('shipment_ids');
            $blueprint->text('notes')->nullable();
            $blueprint->string('status')->default('completed');
            $blueprint->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipment_reconciliations');
    }
};


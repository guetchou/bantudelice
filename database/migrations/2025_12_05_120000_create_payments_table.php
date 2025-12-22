<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable(); // rempli après création commande
            $table->string('provider')->nullable();             // momo, stripe, paypal, cash...
            $table->string('provider_reference')->nullable();   // ID transaction renvoyé par le PSP
            $table->enum('status', [
                'PENDING',      // init, en attente de validation
                'AUTHORIZED',   // autorisé mais pas capturé (si applicable)
                'PAID',         // payé confirmé
                'FAILED',       // échec
                'CANCELLED',
            ])->default('PENDING');
            $table->unsignedInteger('amount');      // montant total en FCFA
            $table->string('currency', 3)->default('XAF');
            $table->json('meta')->nullable();       // détails bruts PSP, logs, etc.
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            
            $table->index('provider_reference');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}



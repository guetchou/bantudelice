<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('gepay_merchants')->restrictOnDelete();
            $table->char('currency', 3)->default('XAF');
            $table->bigInteger('available')->default(0);
            $table->bigInteger('pending')->default(0);
            $table->bigInteger('reserved')->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['merchant_id', 'currency']);
        });

        // CHECK constraints — enforced on MySQL and SQLite 3.25+
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE gepay_wallets ADD CONSTRAINT gepay_wallets_available_check CHECK (available >= 0)'
            );
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE gepay_wallets ADD CONSTRAINT gepay_wallets_pending_check CHECK (pending >= 0)'
            );
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE gepay_wallets ADD CONSTRAINT gepay_wallets_reserved_check CHECK (reserved >= 0)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_wallets');
    }
};

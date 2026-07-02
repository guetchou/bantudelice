<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gepay_merchant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('gepay_merchants')->restrictOnDelete();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->string('password');
            $table->string('role', 32)->default('admin');
            $table->boolean('is_active')->default(true);
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gepay_merchant_users');
    }
};

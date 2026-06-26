<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurant_staff_members')) {
            return;
        }

        Schema::create('restaurant_staff_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 40)->default('viewer');
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamp('last_access_at')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'user_id'], 'restaurant_staff_unique');
            $table->index(['user_id', 'is_active'], 'restaurant_staff_user_active');
            $table->index(['restaurant_id', 'role'], 'restaurant_staff_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_staff_members');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T1.1/T1.2 — Fermeture temporaire restaurant (E2C Brazzaville).
 * is_paused      : true = fermé temporairement (coupure, météo, surcharge)
 * paused_until   : null = pas de réouverture auto, sinon timestamp de réouverture
 * pause_reason   : e2c | weather | overloaded | manual | other
 * last_activity_at : dernière activité (acceptation/refus commande) — pour auto-pause
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('is_paused')->default(false)->after('approved');
            $table->timestamp('paused_until')->nullable()->after('is_paused');
            $table->string('pause_reason', 50)->nullable()->after('paused_until');
            $table->timestamp('last_activity_at')->nullable()->after('pause_reason');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['is_paused', 'paused_until', 'pause_reason', 'last_activity_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Compatibility migration intentionally left empty.
        // The previous STORED generated column forced MySQL to rebuild the
        // payments table and could fail while recreating valid foreign keys
        // with SQLSTATE HY000 / error 1215.
        //
        // Payment and delivery uniqueness is now applied explicitly after a
        // clean integrity audit by the food integrity constraint command.
    }

    public function down(): void
    {
        // Nothing to roll back.
    }
};

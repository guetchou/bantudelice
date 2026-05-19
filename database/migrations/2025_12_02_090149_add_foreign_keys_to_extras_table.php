<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('extras')) {
            Schema::table('extras', function (Blueprint $table) {
                if (! $this->foreignKeyExists('extras', 'extras_product_id_foreign')) {
                    $table->foreign('product_id')
                        ->references('id')
                        ->on('products')
                        ->onDelete('cascade');
                }

                if (! $this->foreignKeyExists('extras', 'extras_type_id_foreign')) {
                    $table->foreign('type_id')
                        ->references('id')
                        ->on('types')
                        ->onDelete('cascade');
                }
            });
        }

        if (Schema::hasTable('cart_extras') && Schema::hasTable('extras')) {
            Schema::table('cart_extras', function (Blueprint $table) {
                if (! $this->foreignKeyExists('cart_extras', 'cart_extras_extra_id_foreign')) {
                    $table->foreign('extra_id')
                        ->references('id')
                        ->on('extras')
                        ->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cart_extras')) {
            Schema::table('cart_extras', function (Blueprint $table) {
                if ($this->foreignKeyExists('cart_extras', 'cart_extras_extra_id_foreign')) {
                    $table->dropForeign('cart_extras_extra_id_foreign');
                }
            });
        }

        if (Schema::hasTable('extras')) {
            Schema::table('extras', function (Blueprint $table) {
                if ($this->foreignKeyExists('extras', 'extras_product_id_foreign')) {
                    $table->dropForeign('extras_product_id_foreign');
                }

                if ($this->foreignKeyExists('extras', 'extras_type_id_foreign')) {
                    $table->dropForeign('extras_type_id_foreign');
                }
            });
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        $database = DB::getDatabaseName();

        return (bool) DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};

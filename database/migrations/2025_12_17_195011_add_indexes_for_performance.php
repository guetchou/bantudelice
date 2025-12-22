<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index pour orders (requêtes fréquentes)
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Index composite pour requêtes par restaurant + statut
                $this->addIndexIfNotExists('orders', 'orders_restaurant_status_index', function() use ($table) {
                    $table->index(['restaurant_id', 'status'], 'orders_restaurant_status_index');
                });
                // Index pour order_no (recherche fréquente)
                $this->addIndexIfNotExists('orders', 'orders_order_no_index', function() use ($table) {
                    $table->index('order_no', 'orders_order_no_index');
                });
                // Index pour user_id + created_at (historique utilisateur)
                $this->addIndexIfNotExists('orders', 'orders_user_created_index', function() use ($table) {
                    $table->index(['user_id', 'created_at'], 'orders_user_created_index');
                });
                // Index pour status + created_at (commandes en attente)
                $this->addIndexIfNotExists('orders', 'orders_status_created_index', function() use ($table) {
                    $table->index(['status', 'created_at'], 'orders_status_created_index');
                });
            });
        }

        // Index pour deliveries
        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $this->addIndexIfNotExists('deliveries', 'deliveries_status_index', function() use ($table) {
                    $table->index('status', 'deliveries_status_index');
                });
                $this->addIndexIfNotExists('deliveries', 'deliveries_driver_status_index', function() use ($table) {
                    $table->index(['driver_id', 'status'], 'deliveries_driver_status_index');
                });
            });
        }

        // Index pour payments
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $this->addIndexIfNotExists('payments', 'payments_status_index', function() use ($table) {
                    $table->index('status', 'payments_status_index');
                });
                $this->addIndexIfNotExists('payments', 'payments_provider_ref_index', function() use ($table) {
                    $table->index(['provider', 'provider_reference'], 'payments_provider_ref_index');
                });
                $this->addIndexIfNotExists('payments', 'payments_user_created_index', function() use ($table) {
                    $table->index(['user_id', 'created_at'], 'payments_user_created_index');
                });
            });
        }

        // Index pour products
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->addIndexIfNotExists('products', 'products_restaurant_available_index', function() use ($table) {
                    $table->index(['restaurant_id', 'is_available'], 'products_restaurant_available_index');
                });
                if (Schema::hasColumn('products', 'featured')) {
                    $this->addIndexIfNotExists('products', 'products_featured_index', function() use ($table) {
                        $table->index('featured', 'products_featured_index');
                    });
                }
            });
        }

        // Index pour categories
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                $this->addIndexIfNotExists('categories', 'categories_restaurant_available_index', function() use ($table) {
                    $table->index(['restaurant_id', 'is_available'], 'categories_restaurant_available_index');
                });
            });
        }

        // Index pour carts
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                $this->addIndexIfNotExists('carts', 'carts_user_index', function() use ($table) {
                    $table->index('user_id', 'carts_user_index');
                });
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les index (optionnel, pour rollback)
        $indexes = [
            'orders' => ['orders_restaurant_status_index', 'orders_order_no_index', 'orders_user_created_index', 'orders_status_created_index'],
            'deliveries' => ['deliveries_status_index', 'deliveries_driver_status_index'],
            'payments' => ['payments_status_index', 'payments_provider_ref_index', 'payments_user_created_index'],
            'products' => ['products_restaurant_available_index', 'products_featured_index'],
            'categories' => ['categories_restaurant_available_index'],
            'carts' => ['carts_user_index'],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($tableIndexes) {
                    foreach ($tableIndexes as $index) {
                        try {
                            $table->dropIndex($index);
                        } catch (\Exception $e) {
                            // Index n'existe pas, ignorer
                        }
                    }
                });
            }
        }
    }

    /**
     * Ajouter un index seulement s'il n'existe pas déjà
     */
    protected function addIndexIfNotExists(string $tableName, string $indexName, callable $callback): void
    {
        if (!$this->hasIndex($tableName, $indexName)) {
            try {
                $callback();
            } catch (\Exception $e) {
                // Ignorer si l'index existe déjà ou autre erreur
            }
        }
    }

    /**
     * Vérifier si un index existe
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'sqlite') {
                $result = DB::select("PRAGMA index_list(\"$table\")");
                foreach ($result as $row) {
                    if ($row->name === $indexName) {
                        return true;
                    }
                }
                return false;
            }

            $database = $connection->getDatabaseName();
            
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$database, $table, $indexName]
            );
            
            return isset($result[0]) && $result[0]->count > 0;
        } catch (\Exception $e) {
            // En cas d'erreur, supposer que l'index n'existe pas
            return false;
        }
    }
};

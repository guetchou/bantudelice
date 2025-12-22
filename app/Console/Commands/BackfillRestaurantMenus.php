<?php

namespace App\Console\Commands;

use App\Category;
use App\Product;
use App\Restaurant;
use App\Services\DataSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BackfillRestaurantMenus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --dry-run : n'écrit rien, affiche uniquement ce qui serait fait
     * --template : force un restaurant modèle (ID)
     * --only-missing : ne traite que les restaurants sans menu (défaut: true)
     */
    protected $signature = 'bantudelice:backfill-menus
        {--dry-run : Ne fait aucune écriture en base, affiche uniquement les actions}
        {--template= : ID du restaurant modèle (optionnel)}
        {--only-missing=1 : 1=ne traiter que les restaurants sans menu, 0=tous}';

    protected $description = 'Renseigne automatiquement un menu pour les restaurants qui n\'ont pas encore de catégories/produits, en clonant un menu existant.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyMissing = ((string) $this->option('only-missing')) !== '0';
        $templateId = $this->option('template');

        $templateRestaurant = null;
        if (!empty($templateId)) {
            $templateRestaurant = Restaurant::where('approved', true)->find($templateId);
            if (!$templateRestaurant) {
                $this->error("Restaurant modèle introuvable ou non approuvé: {$templateId}");
                return self::FAILURE;
            }
        } else {
            $templateRestaurant = Restaurant::where('approved', true)
                ->withCount('products')
                ->orderByDesc('products_count')
                ->first();
        }

        if (!$templateRestaurant) {
            $this->error('Aucun restaurant approuvé trouvé.');
            return self::FAILURE;
        }

        $templateCategories = $templateRestaurant->categories()
            ->with(['products' => function ($q) {
                if (Schema::hasColumn('products', 'sort_order')) {
                    $q->orderBy('sort_order');
                }
                $q->orderBy('featured', 'desc')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $templateProductsCount = $templateCategories->sum(fn ($c) => $c->products->count());
        if ($templateCategories->count() === 0 || $templateProductsCount === 0) {
            $this->error("Le restaurant modèle (#{$templateRestaurant->id}) n'a pas de menu (catégories/produits).");
            return self::FAILURE;
        }

        $this->info("Restaurant modèle: #{$templateRestaurant->id} {$templateRestaurant->name} (catégories={$templateCategories->count()}, produits={$templateProductsCount})");

        $targetsQuery = Restaurant::where('approved', true)->where('id', '!=', $templateRestaurant->id);
        $targets = $targetsQuery->orderBy('id')->get();

        $targets = $targets->filter(function (Restaurant $r) use ($onlyMissing) {
            if (!$onlyMissing) {
                return true;
            }
            // "Sans menu" = aucune catégorie avec au moins 1 produit
            return !$r->categories()->whereHas('products')->exists();
        })->values();

        if ($targets->count() === 0) {
            $this->info('Aucun restaurant cible à traiter.');
            return self::SUCCESS;
        }

        $this->info("Restaurants cibles: {$targets->count()} (only-missing=" . ($onlyMissing ? '1' : '0') . ", dry-run=" . ($dryRun ? '1' : '0') . ')');

        $totalCreatedCategories = 0;
        $totalCreatedProducts = 0;

        foreach ($targets as $restaurant) {
            $this->line("→ Traitement #{$restaurant->id} {$restaurant->name}");

            if ($dryRun) {
                $this->line("  [dry-run] créer/cloner {$templateCategories->count()} catégories et {$templateProductsCount} produits");
                continue;
            }

            DB::beginTransaction();
            try {
                $categoryMap = [];

                foreach ($templateCategories as $tplCategory) {
                    $attrs = [
                        'restaurant_id' => $restaurant->id,
                        'name' => $tplCategory->name,
                    ];

                    $defaults = [];
                    if (Schema::hasColumn('categories', 'is_available')) {
                        $defaults['is_available'] = true;
                    }
                    if (Schema::hasColumn('categories', 'sort_order')) {
                        $defaults['sort_order'] = (int) ($tplCategory->sort_order ?? 0);
                    }

                    $newCategory = Category::firstOrCreate($attrs, $defaults);
                    $categoryMap[(int) $tplCategory->id] = $newCategory;
                    if ($newCategory->wasRecentlyCreated) {
                        $totalCreatedCategories++;
                    }
                }

                foreach ($templateCategories as $tplCategory) {
                    $newCategory = $categoryMap[(int) $tplCategory->id] ?? null;
                    if (!$newCategory) {
                        continue;
                    }

                    foreach ($tplCategory->products as $tplProduct) {
                        $where = [
                            'restaurant_id' => $restaurant->id,
                            'name' => $tplProduct->name,
                        ];

                        $create = [
                            'restaurant_id' => $restaurant->id,
                            'category_id' => $newCategory->id,
                            'name' => $tplProduct->name,
                            'price' => $tplProduct->price,
                            'discount_price' => $tplProduct->discount_price ?? 0,
                            'image' => $tplProduct->image,
                        ];

                        if (Schema::hasColumn('products', 'description')) {
                            $create['description'] = $tplProduct->description;
                        }
                        if (Schema::hasColumn('products', 'size')) {
                            $create['size'] = $tplProduct->size;
                        }
                        if (Schema::hasColumn('products', 'featured')) {
                            $create['featured'] = (int) ($tplProduct->featured ?? 0);
                        }
                        if (Schema::hasColumn('products', 'is_available')) {
                            $create['is_available'] = (bool) ($tplProduct->is_available ?? true);
                        }
                        if (Schema::hasColumn('products', 'sort_order')) {
                            $create['sort_order'] = (int) ($tplProduct->sort_order ?? 0);
                        }

                        $product = Product::firstOrCreate($where, $create);
                        if (!$product->wasRecentlyCreated) {
                            // S'assurer que la catégorie est cohérente si le produit existait déjà
                            if ((int) $product->category_id !== (int) $newCategory->id) {
                                $product->category_id = $newCategory->id;
                                $product->save();
                            }
                        } else {
                            $totalCreatedProducts++;
                        }
                    }
                }

                DB::commit();
                DataSyncService::invalidateRestaurantCache($restaurant->id);

                $this->info("  OK: menu cloné vers #{$restaurant->id}");
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('BackfillRestaurantMenus: échec', [
                    'restaurant_id' => $restaurant->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ERREUR: {$e->getMessage()}");
                return self::FAILURE;
            }
        }

        if (!$dryRun) {
            DataSyncService::invalidateAllCache();
        }

        $this->info("Terminé. Catégories créées: {$totalCreatedCategories} | Produits créés: {$totalCreatedProducts}");
        return self::SUCCESS;
    }
}



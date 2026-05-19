<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Restaurant;
use App\Cuisine;
use App\Category;
use App\Product;
use Illuminate\Support\Facades\DB;

class RestaurantDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Récupérer le restaurant existant ou créer un nouveau
        $restaurant = Restaurant::where('approved', true)->first();
        
        if (!$restaurant) {
            // Créer un restaurant si aucun n'existe
            $restaurant = Restaurant::create([
                'user_id' => 1,
                'name' => 'Chez Gaspard',
                'email' => 'gaspard@example.com',
                'phone' => '+242 06 123 4567',
                'city' => 'Brazzaville',
                'address' => 'Avenue de l\'Indépendance, Brazzaville',
                'description' => 'Restaurant traditionnel congolais, spécialisé dans les plats locaux authentiques',
                'slogan' => 'Le goût authentique du Congo',
                'latitude' => '-4.2767',
                'longitude' => '15.2832',
                'min_order' => 5000,
                'delivery_charges' => 2000,
                'avg_delivery_time' => '00:30:00',
                'approved' => true,
                'featured' => true,
            ]);
        }

        // 1. Créer les cuisines
        $cuisines = [
            ['name' => 'Cuisine Congolaise', 'description' => 'Plats traditionnels congolais'],
            ['name' => 'Fast Food', 'description' => 'Restauration rapide'],
            ['name' => 'Pizza', 'description' => 'Pizzas et plats italiens'],
            ['name' => 'Grillades', 'description' => 'Viandes grillées'],
            ['name' => 'Poissons', 'description' => 'Plats à base de poissons'],
        ];

        $createdCuisines = [];
        foreach ($cuisines as $cuisineData) {
            $cuisine = Cuisine::firstOrCreate(
                ['name' => $cuisineData['name']],
                $cuisineData
            );
            $createdCuisines[$cuisineData['name']] = $cuisine;
        }

        // Associer le restaurant aux cuisines
        $restaurant->cuisines()->sync([
            $createdCuisines['Cuisine Congolaise']->id,
            $createdCuisines['Grillades']->id,
            $createdCuisines['Poissons']->id,
        ]);

        // 2. Créer les catégories pour le restaurant
        $categories = [
            ['name' => 'Plats Principaux', 'restaurant_id' => $restaurant->id],
            ['name' => 'Grillades', 'restaurant_id' => $restaurant->id],
            ['name' => 'Poissons', 'restaurant_id' => $restaurant->id],
            ['name' => 'Accompagnements', 'restaurant_id' => $restaurant->id],
            ['name' => 'Boissons', 'restaurant_id' => $restaurant->id],
            ['name' => 'Desserts', 'restaurant_id' => $restaurant->id],
        ];

        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $category = Category::firstOrCreate(
                ['name' => $categoryData['name'], 'restaurant_id' => $restaurant->id],
                $categoryData
            );
            $createdCategories[$categoryData['name']] = $category;
        }

        // 3. Créer les produits avec prix réalistes (en FCFA)
        $products = [
            // Plats Principaux
            [
                'name' => 'Poulet Moambé',
                'description' => 'Poulet cuit dans une sauce à base de noix de palme, accompagné de riz',
                'price' => 6500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Plats Principaux']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'poulet-moambe.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Saka-Saka',
                'description' => 'Feuilles de manioc pilées avec poisson ou viande',
                'price' => 5500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Plats Principaux']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'saka-saka.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Foufou',
                'description' => 'Pâte de manioc ou banane plantain, servi avec sauce',
                'price' => 4500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Plats Principaux']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'foufou.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Riz au Poulet',
                'description' => 'Riz blanc accompagné de poulet grillé et sauce',
                'price' => 5000,
                'discount_price' => 4500,
                'category_id' => $createdCategories['Plats Principaux']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'riz-poulet.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Pondu',
                'description' => 'Feuilles de manioc cuites avec poisson fumé',
                'price' => 6000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Plats Principaux']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'pondu.jpg',
                'featured' => true,
            ],

            // Grillades
            [
                'name' => 'Brochette de Bœuf',
                'description' => 'Brochettes de bœuf marinées et grillées',
                'price' => 4000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Grillades']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'brochette-boeuf.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Poulet Grillé',
                'description' => 'Poulet entier grillé au charbon de bois',
                'price' => 7000,
                'discount_price' => 6500,
                'category_id' => $createdCategories['Grillades']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'poulet-grille.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Côte de Porc',
                'description' => 'Côtes de porc grillées avec sauce',
                'price' => 5500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Grillades']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'cote-porc.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Brochette de Poulet',
                'description' => 'Brochettes de poulet marinées et grillées',
                'price' => 3500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Grillades']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'brochette-poulet.jpg',
                'featured' => false,
            ],

            // Poissons
            [
                'name' => 'Capitaine Frit',
                'description' => 'Capitaine frit servi avec riz et légumes',
                'price' => 8000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Poissons']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'capitaine-frit.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Poisson Braisé',
                'description' => 'Poisson braisé au charbon avec sauce piquante',
                'price' => 7500,
                'discount_price' => 7000,
                'category_id' => $createdCategories['Poissons']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'poisson-braise.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Poisson à la Sauce',
                'description' => 'Poisson cuit dans une sauce tomate épicée',
                'price' => 7000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Poissons']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'poisson-sauce.jpg',
                'featured' => false,
            ],

            // Accompagnements
            [
                'name' => 'Riz Blanc',
                'description' => 'Riz blanc cuit à la vapeur',
                'price' => 1500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Accompagnements']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'riz-blanc.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Plantain Frit',
                'description' => 'Bananes plantains frites',
                'price' => 2000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Accompagnements']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'plantain-frit.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Frites',
                'description' => 'Pommes de terre frites',
                'price' => 2000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Accompagnements']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'frites.jpg',
                'featured' => false,
            ],

            // Boissons
            [
                'name' => 'Jus de Bissap',
                'description' => 'Jus d\'hibiscus frais',
                'price' => 1500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Boissons']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'jus-bissap.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Jus de Gingembre',
                'description' => 'Jus de gingembre frais',
                'price' => 2000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Boissons']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'jus-gingembre.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Coca Cola',
                'description' => 'Boisson gazeuse',
                'price' => 1500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Boissons']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'coca-cola.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Eau Minérale',
                'description' => 'Eau minérale 1.5L',
                'price' => 1000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Boissons']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'eau-minerale.jpg',
                'featured' => false,
            ],

            // Desserts
            [
                'name' => 'Fruit de la Passion',
                'description' => 'Fruit frais',
                'price' => 1500,
                'discount_price' => 0,
                'category_id' => $createdCategories['Desserts']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'fruit-passion.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Ananas Frais',
                'description' => 'Ananas frais découpé',
                'price' => 2000,
                'discount_price' => 0,
                'category_id' => $createdCategories['Desserts']->id,
                'restaurant_id' => $restaurant->id,
                'image' => 'ananas-frais.jpg',
                'featured' => false,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        // 4. Créer un deuxième restaurant (Fast Food)
        $restaurant2 = Restaurant::firstOrCreate(
            ['email' => 'fastfood@example.com'],
            [
                'user_id' => 1,
                'name' => 'Fast Food Express',
                'phone' => '+242 06 234 5678',
                'city' => 'Brazzaville',
                'address' => 'Boulevard Denis Sassou Nguesso, Brazzaville',
                'description' => 'Fast food moderne avec burgers, pizzas et plats rapides',
                'slogan' => 'Rapide, délicieux, abordable',
                'latitude' => '-4.2800',
                'longitude' => '15.2900',
                'min_order' => 3000,
                'delivery_charges' => 1500,
                'avg_delivery_time' => '00:25:00',
                'approved' => true,
                'featured' => true,
            ]
        );

        // Associer le 2ème restaurant aux cuisines
        $restaurant2->cuisines()->sync([
            $createdCuisines['Fast Food']->id,
            $createdCuisines['Pizza']->id,
        ]);

        // Catégories pour le 2ème restaurant
        $categories2 = [
            ['name' => 'Burgers', 'restaurant_id' => $restaurant2->id],
            ['name' => 'Pizzas', 'restaurant_id' => $restaurant2->id],
            ['name' => 'Sandwichs', 'restaurant_id' => $restaurant2->id],
            ['name' => 'Frites & Accompagnements', 'restaurant_id' => $restaurant2->id],
            ['name' => 'Boissons', 'restaurant_id' => $restaurant2->id],
        ];

        $createdCategories2 = [];
        foreach ($categories2 as $categoryData) {
            $category = Category::firstOrCreate(
                ['name' => $categoryData['name'], 'restaurant_id' => $restaurant2->id],
                $categoryData
            );
            $createdCategories2[$categoryData['name']] = $category;
        }

        // Produits pour le 2ème restaurant
        $products2 = [
            // Burgers
            [
                'name' => 'Burger Classique',
                'description' => 'Pain, steak, salade, tomate, oignons, sauce',
                'price' => 3500,
                'discount_price' => 3000,
                'category_id' => $createdCategories2['Burgers']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'burger-classique.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Burger Poulet',
                'description' => 'Pain, filet de poulet, salade, tomate, sauce',
                'price' => 4000,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Burgers']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'burger-poulet.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Burger Double',
                'description' => 'Double steak, double fromage, salade, tomate',
                'price' => 5000,
                'discount_price' => 4500,
                'category_id' => $createdCategories2['Burgers']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'burger-double.jpg',
                'featured' => true,
            ],

            // Pizzas
            [
                'name' => 'Pizza Margherita',
                'description' => 'Tomate, mozzarella, basilic',
                'price' => 6000,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Pizzas']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'pizza-margherita.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Pizza Reine',
                'description' => 'Tomate, mozzarella, jambon, champignons',
                'price' => 7000,
                'discount_price' => 6500,
                'category_id' => $createdCategories2['Pizzas']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'pizza-reine.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Pizza 4 Fromages',
                'description' => 'Mozzarella, gorgonzola, parmesan, chèvre',
                'price' => 7500,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Pizzas']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'pizza-4-fromages.jpg',
                'featured' => false,
            ],

            // Sandwichs
            [
                'name' => 'Sandwich Poulet',
                'description' => 'Pain, poulet, salade, tomate, mayonnaise',
                'price' => 3000,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Sandwichs']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'sandwich-poulet.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Sandwich Thon',
                'description' => 'Pain, thon, salade, tomate, mayonnaise',
                'price' => 3500,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Sandwichs']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'sandwich-thon.jpg',
                'featured' => false,
            ],

            // Frites
            [
                'name' => 'Frites',
                'description' => 'Portion de frites',
                'price' => 2000,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Frites & Accompagnements']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'frites.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Frites + Sauce',
                'description' => 'Frites avec sauce au choix',
                'price' => 2500,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Frites & Accompagnements']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'frites-sauce.jpg',
                'featured' => false,
            ],

            // Boissons
            [
                'name' => 'Coca Cola',
                'description' => 'Boisson gazeuse',
                'price' => 1500,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Boissons']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'coca-cola.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Jus d\'Orange',
                'description' => 'Jus d\'orange frais',
                'price' => 2000,
                'discount_price' => 0,
                'category_id' => $createdCategories2['Boissons']->id,
                'restaurant_id' => $restaurant2->id,
                'image' => 'jus-orange.jpg',
                'featured' => false,
            ],
        ];

        foreach ($products2 as $productData) {
            Product::create($productData);
        }

        echo "✅ Données de test créées avec succès !\n";
        echo "   - 2 restaurants\n";
        echo "   - " . count($cuisines) . " cuisines\n";
        echo "   - " . (count($categories) + count($categories2)) . " catégories\n";
        echo "   - " . (count($products) + count($products2)) . " produits\n";
    }
}


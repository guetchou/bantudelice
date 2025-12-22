<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CongoTestDataSeeder extends Seeder
{
    /**
     * Seed des données de test pour BantuDelice
     * Restaurants congolais réels, livreurs et produits
     */
    public function run()
    {
        $now = Carbon::now();
        
        // ========================================
        // 1. UTILISATEUR DE TEST CLIENT
        // ========================================
        $testUserId = DB::table('users')->insertGetId([
            'name' => 'Client Test BantuDelice',
            'email' => 'client@bantudelice.cg',
            'password' => Hash::make('test123456'),
            'phone' => '+242 06 500 00 01',
            'type' => 'user',
            'blocked' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        echo "✓ Utilisateur client créé: client@bantudelice.cg / test123456\n";
        
        // ========================================
        // 2. UTILISATEURS RESTAURANTS
        // ========================================
        $restaurantUsers = [];
        $restaurants = [
            [
                'name' => 'Mami Wata Restaurant',
                'email' => 'mamiwata@bantudelice.cg',
                'slogan' => 'La cuisine congolaise authentique',
                'address' => 'Avenue de l\'Indépendance, Brazzaville',
                'phone' => '+242 06 600 00 01',
                'description' => 'Restaurant traditionnel congolais proposant les meilleurs plats locaux : saka-saka, pondu, poisson braisé et viandes grillées.',
                'lat' => -4.2658,
                'lng' => 15.2832,
            ],
            [
                'name' => 'Chez Gaspard',
                'email' => 'chezgaspard@bantudelice.cg',
                'slogan' => 'Excellence gastronomique congolaise',
                'address' => 'Boulevard Denis Sassou Nguesso, Brazzaville',
                'phone' => '+242 06 600 00 02',
                'description' => 'Restaurant haut de gamme spécialisé dans la cuisine congolaise revisitée avec une touche moderne.',
                'lat' => -4.2712,
                'lng' => 15.2745,
            ],
            [
                'name' => 'Le Hippopotame',
                'email' => 'hippopotame@bantudelice.cg',
                'slogan' => 'Grillades et spécialités locales',
                'address' => 'Corniche, Brazzaville',
                'phone' => '+242 06 600 00 03',
                'description' => 'Restaurant avec vue sur le fleuve Congo, spécialisé dans les grillades de poisson et viandes.',
                'lat' => -4.2589,
                'lng' => 15.2901,
            ],
            [
                'name' => 'Pili Pili',
                'email' => 'pilipili@bantudelice.cg',
                'slogan' => 'Épices et saveurs d\'Afrique',
                'address' => 'Centre-ville, Pointe-Noire',
                'phone' => '+242 06 600 00 04',
                'description' => 'Restaurant panafricain proposant des plats épicés du Congo et d\'ailleurs.',
                'lat' => -4.7761,
                'lng' => 11.8664,
            ],
            [
                'name' => 'La Mandarine',
                'email' => 'mandarine@bantudelice.cg',
                'slogan' => 'Fusion afro-asiatique',
                'address' => 'Quartier Plateau, Brazzaville',
                'phone' => '+242 06 600 00 05',
                'description' => 'Restaurant moderne proposant une cuisine fusion entre saveurs africaines et asiatiques.',
                'lat' => -4.2634,
                'lng' => 15.2429,
            ],
            [
                'name' => 'Nganda Ya Mboka',
                'email' => 'nganda@bantudelice.cg',
                'slogan' => 'Le goût du village',
                'address' => 'Bacongo, Brazzaville',
                'phone' => '+242 06 600 00 06',
                'description' => 'Authentique nganda proposant des plats traditionnels dans une ambiance villageoise.',
                'lat' => -4.2845,
                'lng' => 15.2678,
            ],
            [
                'name' => 'Le Pescador',
                'email' => 'pescador@bantudelice.cg',
                'slogan' => 'Fruits de mer et poissons frais',
                'address' => 'Front de mer, Pointe-Noire',
                'phone' => '+242 06 600 00 07',
                'description' => 'Restaurant de fruits de mer avec les produits les plus frais de l\'océan Atlantique.',
                'lat' => -4.7892,
                'lng' => 11.8512,
            ],
            [
                'name' => 'Espace Malebo',
                'email' => 'malebo@bantudelice.cg',
                'slogan' => 'Vue panoramique et bonne cuisine',
                'address' => 'Malebo, Brazzaville',
                'phone' => '+242 06 600 00 08',
                'description' => 'Restaurant avec terrasse offrant une vue imprenable sur le Pool Malebo et une cuisine variée.',
                'lat' => -4.2501,
                'lng' => 15.3012,
            ],
        ];
        
        foreach ($restaurants as $resto) {
            $userId = DB::table('users')->insertGetId([
                'name' => $resto['name'],
                'email' => $resto['email'],
                'password' => Hash::make('resto123456'),
                'phone' => $resto['phone'],
                'type' => 'restaurant',
                'blocked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $restaurantUsers[$resto['email']] = $userId;
        }
        
        echo "✓ " . count($restaurants) . " utilisateurs restaurant créés\n";
        
        // ========================================
        // 3. CUISINES
        // ========================================
        $cuisines = [
            'Cuisine Congolaise',
            'Grillades',
            'Fruits de Mer',
            'Cuisine Africaine',
            'Cuisine Fusion',
            'Fast Food',
            'Pâtisserie',
            'Boissons & Cocktails',
        ];
        
        $cuisineIds = [];
        foreach ($cuisines as $cuisine) {
            $cuisineIds[$cuisine] = DB::table('cuisines')->insertGetId([
                'name' => $cuisine,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        echo "✓ " . count($cuisines) . " cuisines créées\n";
        
        // ========================================
        // 4. RESTAURANTS
        // ========================================
        $restaurantData = [
            [
                'email' => 'mamiwata@bantudelice.cg',
                'data' => $restaurants[0],
                'cuisines' => ['Cuisine Congolaise', 'Grillades'],
            ],
            [
                'email' => 'chezgaspard@bantudelice.cg',
                'data' => $restaurants[1],
                'cuisines' => ['Cuisine Congolaise', 'Cuisine Fusion'],
            ],
            [
                'email' => 'hippopotame@bantudelice.cg',
                'data' => $restaurants[2],
                'cuisines' => ['Grillades', 'Fruits de Mer'],
            ],
            [
                'email' => 'pilipili@bantudelice.cg',
                'data' => $restaurants[3],
                'cuisines' => ['Cuisine Africaine', 'Grillades'],
            ],
            [
                'email' => 'mandarine@bantudelice.cg',
                'data' => $restaurants[4],
                'cuisines' => ['Cuisine Fusion', 'Cuisine Africaine'],
            ],
            [
                'email' => 'nganda@bantudelice.cg',
                'data' => $restaurants[5],
                'cuisines' => ['Cuisine Congolaise'],
            ],
            [
                'email' => 'pescador@bantudelice.cg',
                'data' => $restaurants[6],
                'cuisines' => ['Fruits de Mer', 'Grillades'],
            ],
            [
                'email' => 'malebo@bantudelice.cg',
                'data' => $restaurants[7],
                'cuisines' => ['Cuisine Congolaise', 'Cuisine Africaine', 'Grillades'],
            ],
        ];
        
        $restaurantIds = [];
        foreach ($restaurantData as $resto) {
            $restoId = DB::table('restaurants')->insertGetId([
                'user_id' => $restaurantUsers[$resto['email']],
                'name' => $resto['data']['name'],
                'user_name' => strtolower(str_replace(' ', '_', $resto['data']['name'])),
                'email' => $resto['email'],
                'password' => Hash::make('resto123456'),
                'slogan' => $resto['data']['slogan'],
                'logo' => null,
                'cover_image' => null,
                'services' => 'both',
                'service_charges' => rand(5, 10),
                'delivery_charges' => rand(1000, 3000),
                'city' => strpos($resto['data']['address'], 'Pointe-Noire') !== false ? 'Pointe-Noire' : 'Brazzaville',
                'tax' => 5,
                'address' => $resto['data']['address'],
                'latitude' => $resto['data']['lat'],
                'longitude' => $resto['data']['lng'],
                'phone' => $resto['data']['phone'],
                'description' => $resto['data']['description'],
                'min_order' => rand(3000, 5000),
                'avg_delivery_time' => rand(25, 45) . ' min',
                'delivery_range' => rand(5, 15),
                'admin_commission' => 15,
                'approved' => 1,
                'featured' => rand(0, 1),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            $restaurantIds[$resto['email']] = $restoId;
            
            // Associer les cuisines
            foreach ($resto['cuisines'] as $cuisineName) {
                DB::table('cuisine_restaurant')->insert([
                    'cuisine_id' => $cuisineIds[$cuisineName],
                    'restaurant_id' => $restoId,
                ]);
            }
        }
        
        echo "✓ " . count($restaurantIds) . " restaurants créés\n";
        
        // ========================================
        // 5. CATÉGORIES PAR RESTAURANT
        // ========================================
        $categories = ['Entrées', 'Plats Principaux', 'Grillades', 'Poissons', 'Accompagnements', 'Boissons', 'Desserts'];
        
        $categoryIds = [];
        foreach ($restaurantIds as $email => $restoId) {
            foreach ($categories as $cat) {
                $catId = DB::table('categories')->insertGetId([
                    'restaurant_id' => $restoId,
                    'name' => $cat,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $categoryIds[$restoId][$cat] = $catId;
            }
        }
        
        echo "✓ Catégories créées pour chaque restaurant\n";
        
        // ========================================
        // 6. PRODUITS (PLATS CONGOLAIS)
        // ========================================
        $plats = [
            // Plats Principaux
            ['name' => 'Saka-Saka au Poisson Fumé', 'category' => 'Plats Principaux', 'price' => 4500, 'desc' => 'Feuilles de manioc pilées accompagnées de poisson fumé et huile de palme'],
            ['name' => 'Pondu au Poulet', 'category' => 'Plats Principaux', 'price' => 5000, 'desc' => 'Feuilles de manioc avec morceaux de poulet tendre'],
            ['name' => 'Liboke de Poisson', 'category' => 'Plats Principaux', 'price' => 6500, 'desc' => 'Poisson cuit à l\'étouffée dans des feuilles de bananier avec épices'],
            ['name' => 'Poulet Moambe', 'category' => 'Plats Principaux', 'price' => 7000, 'desc' => 'Poulet mijoté dans une sauce à base de noix de palme'],
            ['name' => 'Mwambe de Viande', 'category' => 'Plats Principaux', 'price' => 7500, 'desc' => 'Viande de bœuf dans sauce de noix de palme'],
            ['name' => 'Ntaba (Chèvre Grillée)', 'category' => 'Grillades', 'price' => 8000, 'desc' => 'Viande de chèvre marinée et grillée au feu de bois'],
            
            // Grillades
            ['name' => 'Brochettes de Bœuf', 'category' => 'Grillades', 'price' => 3500, 'desc' => 'Brochettes de bœuf marinées aux épices locales'],
            ['name' => 'Poulet Braisé', 'category' => 'Grillades', 'price' => 5500, 'desc' => 'Demi-poulet grillé au charbon de bois'],
            ['name' => 'Côtes de Porc Grillées', 'category' => 'Grillades', 'price' => 6000, 'desc' => 'Côtes de porc marinées et grillées'],
            ['name' => 'Capitaine Braisé', 'category' => 'Poissons', 'price' => 8500, 'desc' => 'Poisson capitaine entier grillé aux épices'],
            
            // Poissons
            ['name' => 'Tilapia Frit', 'category' => 'Poissons', 'price' => 5000, 'desc' => 'Tilapia frais frit accompagné de sauce tomate'],
            ['name' => 'Maboke de Tilapia', 'category' => 'Poissons', 'price' => 6000, 'desc' => 'Tilapia cuit dans des feuilles de bananier'],
            ['name' => 'Crevettes à l\'Ail', 'category' => 'Poissons', 'price' => 9000, 'desc' => 'Crevettes fraîches sautées à l\'ail et piment'],
            
            // Accompagnements
            ['name' => 'Fufu', 'category' => 'Accompagnements', 'price' => 1500, 'desc' => 'Pâte de manioc traditionnelle'],
            ['name' => 'Chikwangue', 'category' => 'Accompagnements', 'price' => 1000, 'desc' => 'Pain de manioc fermenté'],
            ['name' => 'Riz Blanc', 'category' => 'Accompagnements', 'price' => 1200, 'desc' => 'Riz blanc parfumé'],
            ['name' => 'Bananes Plantains Frites', 'category' => 'Accompagnements', 'price' => 1500, 'desc' => 'Plantains mûrs frits dorés'],
            ['name' => 'Frites de Manioc', 'category' => 'Accompagnements', 'price' => 1500, 'desc' => 'Bâtonnets de manioc frits croustillants'],
            
            // Entrées
            ['name' => 'Salade Congolaise', 'category' => 'Entrées', 'price' => 2500, 'desc' => 'Salade fraîche avec légumes locaux'],
            ['name' => 'Beignets de Haricots', 'category' => 'Entrées', 'price' => 1500, 'desc' => 'Beignets croustillants de haricots'],
            ['name' => 'Samoussa Viande', 'category' => 'Entrées', 'price' => 2000, 'desc' => 'Samoussa farcis à la viande épicée'],
            
            // Boissons
            ['name' => 'Jus de Gingembre', 'category' => 'Boissons', 'price' => 1000, 'desc' => 'Jus frais de gingembre maison'],
            ['name' => 'Jus de Bissap', 'category' => 'Boissons', 'price' => 1000, 'desc' => 'Infusion d\'hibiscus rafraîchissante'],
            ['name' => 'Jus de Corossol', 'category' => 'Boissons', 'price' => 1500, 'desc' => 'Jus frais de corossol'],
            ['name' => 'Tangawis', 'category' => 'Boissons', 'price' => 1200, 'desc' => 'Mélange citron-gingembre-miel'],
            ['name' => 'Primus (Bière)', 'category' => 'Boissons', 'price' => 1500, 'desc' => 'Bière locale congolaise'],
            ['name' => 'Ngok (Bière)', 'category' => 'Boissons', 'price' => 1500, 'desc' => 'Bière blonde congolaise'],
            
            // Desserts
            ['name' => 'Beignets Sucrés', 'category' => 'Desserts', 'price' => 1500, 'desc' => 'Beignets soufflés au sucre'],
            ['name' => 'Fruits de Saison', 'category' => 'Desserts', 'price' => 2000, 'desc' => 'Assortiment de fruits tropicaux frais'],
            ['name' => 'Gâteau au Manioc', 'category' => 'Desserts', 'price' => 2500, 'desc' => 'Gâteau traditionnel à base de manioc râpé'],
        ];
        
        $productCount = 0;
        foreach ($restaurantIds as $email => $restoId) {
            // Ajouter une sélection aléatoire de plats pour chaque restaurant
            $selectedPlats = array_rand($plats, min(15, count($plats)));
            if (!is_array($selectedPlats)) $selectedPlats = [$selectedPlats];
            
            foreach ($selectedPlats as $index) {
                $plat = $plats[$index];
                DB::table('products')->insert([
                    'category_id' => $categoryIds[$restoId][$plat['category']],
                    'restaurant_id' => $restoId,
                    'name' => $plat['name'],
                    'image' => 'default-food.jpg',
                    'price' => $plat['price'],
                    'discount_price' => rand(0, 1) ? round($plat['price'] * 0.9) : null,
                    'description' => $plat['desc'],
                    'featured' => rand(0, 1),
                    'size' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $productCount++;
            }
        }
        
        echo "✓ {$productCount} produits créés\n";
        
        // ========================================
        // 7. LIVREURS
        // ========================================
        $livreurs = [
            ['name' => 'Jean-Paul Mboumba', 'phone' => '+242 06 700 00 01', 'address' => 'Poto-Poto, Brazzaville'],
            ['name' => 'Patrick Ndoudi', 'phone' => '+242 06 700 00 02', 'address' => 'Bacongo, Brazzaville'],
            ['name' => 'Serge Makaya', 'phone' => '+242 06 700 00 03', 'address' => 'Moungali, Brazzaville'],
            ['name' => 'Alain Mouanda', 'phone' => '+242 06 700 00 04', 'address' => 'Ouenzé, Brazzaville'],
            ['name' => 'David Malonga', 'phone' => '+242 06 700 00 05', 'address' => 'Talangaï, Brazzaville'],
            ['name' => 'Christian Nkoua', 'phone' => '+242 06 700 00 06', 'address' => 'Mfilou, Brazzaville'],
            ['name' => 'Fabrice Okemba', 'phone' => '+242 06 700 00 07', 'address' => 'Tie-Tie, Pointe-Noire'],
            ['name' => 'Rodrigue Mbemba', 'phone' => '+242 06 700 00 08', 'address' => 'Loandjili, Pointe-Noire'],
            ['name' => 'Hervé Ngoma', 'phone' => '+242 06 700 00 09', 'address' => 'Centre-ville, Pointe-Noire'],
            ['name' => 'Thierry Bakala', 'phone' => '+242 06 700 00 10', 'address' => 'Mpila, Brazzaville'],
        ];
        
        // Récupérer le premier restaurant pour associer les livreurs
        $firstRestoId = array_values($restaurantIds)[0];
        
        foreach ($livreurs as $livreur) {
            DB::table('drivers')->insert([
                'restaurant_id' => $firstRestoId,
                'name' => $livreur['name'],
                'user_name' => strtolower(str_replace(' ', '_', $livreur['name'])),
                'phone' => $livreur['phone'],
                'email' => strtolower(str_replace(' ', '.', $livreur['name'])) . '@bantudelice.cg',
                'image' => null,
                'password' => Hash::make('driver123456'),
                'hourly_pay' => rand(1500, 3000),
                'address' => $livreur['address'],
                'cnic' => 'CG' . rand(100000000, 999999999),
                'approved' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        echo "✓ " . count($livreurs) . " livreurs créés\n";
        
        // ========================================
        // 8. CHARGES (FRAIS DE SERVICE)
        // ========================================
        DB::table('charges')->updateOrInsert(
            ['id' => 1],
            [
                'delivery_fee' => 1500,
                'tax' => 5,
                'service_fee' => 3,
                'min_order' => 3000,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        
        echo "✓ Frais de service configurés\n";
        
        // ========================================
        // 9. UTILISATEUR ADMIN DE TEST
        // ========================================
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@bantudelice.cg'],
            [
                'name' => 'Admin BantuDelice',
                'email' => 'admin@bantudelice.cg',
                'password' => Hash::make('admin123456'),
                'phone' => '+242 06 000 00 00',
                'type' => 'admin',
                'blocked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        
        echo "✓ Admin créé: admin@bantudelice.cg / admin123456\n";
        
        // ========================================
        // 10. RATINGS DE TEST
        // ========================================
        foreach ($restaurantIds as $restoId) {
            for ($i = 0; $i < rand(3, 8); $i++) {
                DB::table('ratings')->insert([
                    'user_id' => $testUserId,
                    'restaurant_id' => $restoId,
                    'rating' => rand(3, 5),
                    'review' => ['Excellent service !', 'Très bon restaurant', 'Livraison rapide', 'Plats délicieux', 'Je recommande !'][rand(0, 4)],
                    'created_at' => $now->copy()->subDays(rand(1, 30)),
                    'updated_at' => $now,
                ]);
            }
        }
        
        echo "✓ Notes et avis créés\n";
        
        // ========================================
        // RÉSUMÉ
        // ========================================
        echo "\n========================================\n";
        echo "DONNÉES DE TEST CRÉÉES AVEC SUCCÈS !\n";
        echo "========================================\n\n";
        echo "COMPTES DE TEST:\n";
        echo "----------------------------------------\n";
        echo "👤 CLIENT:     client@bantudelice.cg / test123456\n";
        echo "👔 ADMIN:      admin@bantudelice.cg / admin123456\n";
        echo "🍽️ RESTAURANT: mamiwata@bantudelice.cg / resto123456\n";
        echo "🛵 LIVREUR:    jean.paul.mboumba@bantudelice.cg / driver123456\n";
        echo "----------------------------------------\n";
    }
}


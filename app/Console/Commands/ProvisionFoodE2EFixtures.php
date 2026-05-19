<?php

namespace App\Console\Commands;

use App\Category;
use App\Driver;
use App\Order;
use App\Payment;
use App\Product;
use App\Restaurant;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProvisionFoodE2EFixtures extends Command
{
    protected $signature = 'e2e:provision-food-flow
        {--password=BdE2E!Food2026 : Mot de passe commun aux comptes E2E food}
        {--json : Retourner uniquement le JSON des fixtures provisionnees}';

    protected $description = 'Provisionne un jeu de donnees food E2E stable, isole et serviceable pour les validations bout en bout.';

    public function handle(): int
    {
        $password = (string) $this->option('password');
        $now = now();

        $fixtures = DB::transaction(function () use ($password, $now) {
            $customer = $this->upsertUser(
                'e2e.food.customer@bantudelice.cg',
                'E2E Food Customer',
                '+2420600881001',
                'user',
                $password
            );

            $restaurantUser = $this->upsertUser(
                'e2e.food.restaurant@bantudelice.cg',
                'E2E Food Restaurant',
                '+2420600881002',
                'restaurant',
                $password
            );

            $restaurant = $this->upsertRestaurant($restaurantUser, $password, $now);

            $driverUser = $this->upsertUser(
                'e2e.food.driver@bantudelice.cg',
                'E2E Food Driver',
                '+2420600881003',
                'driver',
                $password
            );

            $driver = $this->upsertDriver($driverUser, $restaurant, $password, $now);

            $this->cleanupOperationalResidue($restaurant, $driver);

            $driver = $driver->fresh();
            $restaurant = $restaurant->fresh();

            $this->syncDriverLocation($driver, $now);

            $categoryPayload = [
                'restaurant_id' => $restaurant->id,
                'name' => 'E2E Food Specials',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('categories', 'sort_order')) {
                $categoryPayload['sort_order'] = 1;
            }
            if (Schema::hasColumn('categories', 'is_available')) {
                $categoryPayload['is_available'] = 1;
            }

            $category = Category::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => 'E2E Food Specials'],
                $this->filterPayloadForTable('categories', $categoryPayload)
            );

            $productPayload = [
                'restaurant_id' => $restaurant->id,
                'category_id' => $category->id,
                'name' => 'Poulet E2E Flow',
                'image' => 'default-food.jpg',
                'price' => 4500,
                'discount_price' => null,
                'description' => 'Produit reserve aux validations E2E du workflow food',
                'featured' => 0,
                'size' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('products', 'sort_order')) {
                $productPayload['sort_order'] = 1;
            }
            if (Schema::hasColumn('products', 'is_available')) {
                $productPayload['is_available'] = 1;
            }

            $product = Product::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => 'Poulet E2E Flow'],
                $this->filterPayloadForTable('products', $productPayload)
            );

            if (Schema::hasTable('carts')) {
                DB::table('carts')->where('user_id', $customer->id)->delete();
            }

            return [
                'base_url' => config('app.url', 'https://bantudelice.cg'),
                'credentials' => [
                    'customer' => [
                        'email' => $customer->email,
                        'password' => $password,
                    ],
                    'restaurant' => [
                        'email' => $restaurantUser->email,
                        'password' => $password,
                    ],
                    'driver' => [
                        'email' => $driverUser->email,
                        'password' => $password,
                    ],
                ],
                'artifacts' => [
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'driver_id' => $driver->id,
                ],
            ];
        });

        if ($this->option('json')) {
            $this->line(json_encode($fixtures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(
            ['Role', 'Email', 'Mot de passe', 'Reference'],
            [
                ['Customer', $fixtures['credentials']['customer']['email'], $password, 'food customer'],
                ['Restaurant', $fixtures['credentials']['restaurant']['email'], $password, 'restaurant_id=' . $fixtures['artifacts']['restaurant_id']],
                ['Driver', $fixtures['credentials']['driver']['email'], $password, 'driver_id=' . $fixtures['artifacts']['driver_id']],
            ]
        );
        $this->line('Produit E2E: ' . $fixtures['artifacts']['product_name'] . ' (#' . $fixtures['artifacts']['product_id'] . ')');

        return self::SUCCESS;
    }

    protected function upsertUser(string $email, string $name, string $phone, string $type, string $password): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->phone = $phone;
        $user->type = $type;
        $user->password = Hash::make($password);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    protected function upsertRestaurant(User $restaurantUser, string $password, $now): Restaurant
    {
        $restaurant = Restaurant::query()->firstOrNew(['email' => $restaurantUser->email]);

        $this->fillExistingColumns($restaurant, 'restaurants', [
            'user_id' => $restaurantUser->id,
            'name' => 'E2E Food Bistro',
            'user_name' => 'e2e-food-bistro',
            'password' => Hash::make($password),
            'slogan' => 'Restaurant reserve aux validations end-to-end',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'city' => 'Brazzaville',
            'address' => 'Avenue Amilcar Cabral, Brazzaville',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => $restaurantUser->phone,
            'email' => $restaurantUser->email,
            'description' => 'Fixtures E2E food dediees',
            'min_order' => 0,
            'avg_delivery_time' => '00:35:00',
            'account_name' => 'E2E Food Bistro',
            'account_number' => 'E2E-FOOD-BISTRO',
            'created_at' => ! $restaurant->exists ? $now : $restaurant->created_at,
            'updated_at' => $now,
        ]);
        $restaurant->save();

        $extra = [
            'service_charges' => 0,
            'delivery_charges' => 500,
            'tax' => 0,
            'delivery_range' => 10,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
        ];

        $this->updateTableColumns('restaurants', ['id' => $restaurant->id], $extra);

        return $restaurant->fresh();
    }

    protected function upsertDriver(User $driverUser, Restaurant $restaurant, string $password, $now): Driver
    {
        $driver = Driver::query()->firstOrNew(['email' => $driverUser->email]);

        $this->fillExistingColumns($driver, 'drivers', [
            'restaurant_id' => $restaurant->id,
            'name' => $driverUser->name,
            'user_name' => 'e2e-food-driver',
            'email' => $driverUser->email,
            'phone' => $driverUser->phone,
            'image' => null,
            'password' => Hash::make($password),
            'address' => 'Avenue de la Paix, Brazzaville',
            'latitude' => -4.2628,
            'longitude' => 15.2438,
            'status' => 'online',
            'approved' => true,
            'created_at' => ! $driver->exists ? $now : $driver->created_at,
            'updated_at' => $now,
        ]);
        $driver->save();

        $this->updateTableColumns('drivers', ['id' => $driver->id], [
            'is_available' => true,
            'status' => 'online',
            'approved' => 1,
            'restaurant_id' => $restaurant->id,
            'latitude' => -4.2628,
            'longitude' => 15.2438,
        ]);

        return $driver->fresh();
    }

    protected function cleanupOperationalResidue(Restaurant $restaurant, Driver $driver): void
    {
        $orderIds = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->pluck('id');

        if ($orderIds->isNotEmpty()) {
            Payment::query()->whereIn('order_id', $orderIds)->delete();
            DB::table('deliveries')->whereIn('order_id', $orderIds)->delete();
            Order::query()->whereIn('id', $orderIds)->delete();
        }

        DB::table('deliveries')->where('driver_id', $driver->id)->delete();

        $this->updateTableColumns('drivers', ['id' => $driver->id], [
            'is_available' => true,
            'status' => 'online',
        ]);
    }

    protected function syncDriverLocation(Driver $driver, $timestamp): void
    {
        if ($driver->latitude === null || $driver->longitude === null) {
            return;
        }

        if (class_exists(\App\DriverLocation::class) && Schema::hasTable('driver_locations')) {
            \App\DriverLocation::query()->create([
                'driver_id' => $driver->id,
                'latitude' => $driver->latitude,
                'longitude' => $driver->longitude,
                'accuracy' => 10,
                'heading' => null,
                'speed' => 0,
                'timestamp' => $timestamp,
            ]);
        }
    }

    protected function filterPayloadForTable(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    protected function updateTableColumns(string $table, array $where, array $payload): void
    {
        $updates = $this->filterPayloadForTable($table, $payload);
        if ($updates === []) {
            return;
        }

        DB::table($table)->where($where)->update($updates);
    }

    protected function fillExistingColumns(object $model, string $table, array $payload): void
    {
        foreach ($this->filterPayloadForTable($table, $payload) as $column => $value) {
            $model->{$column} = $value;
        }
    }
}

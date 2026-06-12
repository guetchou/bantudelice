<?php

use App\Category;
use App\Delivery;
use App\Driver;
use App\Order;
use App\Product;
use App\Restaurant;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

$now = now();

$credentials = [
    'admin' => [
        'email' => 'e2e.admin@bantudelice.cg',
        'password' => 'BdE2E!Admin2026',
        'phone' => '+2420600990001',
        'name' => 'E2E Admin',
        'type' => 'admin',
    ],
    'restaurant' => [
        'email' => 'e2e.restaurant@bantudelice.cg',
        'password' => 'BdE2E!Resto2026',
        'phone' => '+2420600990002',
        'name' => 'E2E Restaurant',
        'type' => 'restaurant',
    ],
    'driver' => [
        'email' => 'e2e.driver@bantudelice.cg',
        'password' => 'BdE2E!Driver2026',
        'phone' => '+2420600990003',
        'name' => 'E2E Driver',
        'type' => 'driver',
    ],
    'customer' => [
        'email' => 'e2e.customer@bantudelice.cg',
        'password' => 'BdE2E!Customer2026',
        'phone' => '+2420600990004',
        'name' => 'E2E Customer',
        'type' => 'user',
    ],
];

$orderNo = 'E2E-DRV-20260327';
$deliveryOtp = '2468';

$users = [];

DB::transaction(function () use ($credentials, $now, &$users, $orderNo, $deliveryOtp) {
    foreach ($credentials as $key => $payload) {
        $users[$key] = User::updateOrCreate(
            ['email' => $payload['email']],
            [
                'name' => $payload['name'],
                'phone' => $payload['phone'],
                'type' => $payload['type'],
                'password' => Hash::make($payload['password']),
                'email_verified_at' => $now,
            ]
        );
    }

    $restaurant = Restaurant::updateOrCreate(
        ['email' => $credentials['restaurant']['email']],
        [
            'user_id' => $users['restaurant']->id,
            'name' => 'E2E Bistro',
            'user_name' => 'e2e-bistro',
            'password' => Hash::make($credentials['restaurant']['password']),
            'slogan' => 'Restaurant de test E2E',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Avenue E2E, Brazzaville',
            'latitude' => null,
            'longitude' => null,
            'phone' => $credentials['restaurant']['phone'],
            'description' => 'Restaurant dédié aux tests end-to-end',
            'min_order' => 0,
            'avg_delivery_time' => '00:35:00',
            'delivery_range' => 10,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'E2E Bistro',
            'account_number' => 'E2E-REST-2026',
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $driver = Driver::updateOrCreate(
        ['email' => $credentials['driver']['email']],
        [
            'restaurant_id' => $restaurant->id,
            'name' => 'E2E Driver',
            'user_name' => 'e2e-driver',
            'phone' => $credentials['driver']['phone'],
            'image' => null,
            'password' => Hash::make($credentials['driver']['password']),
            'hourly_pay' => 0,
            'address' => 'Quartier E2E',
            'cnic' => 'E2ECNIC20260327',
            'approved' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $categoryPayload = [
        'restaurant_id' => $restaurant->id,
        'name' => 'E2E Specials',
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
        ['restaurant_id' => $restaurant->id, 'name' => 'E2E Specials'],
        $categoryPayload
    );

    $productPayload = [
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'name' => 'Poulet E2E',
        'image' => 'default-food.jpg',
        'price' => 4500,
        'discount_price' => null,
        'description' => 'Produit dédié aux tests end-to-end',
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
        ['restaurant_id' => $restaurant->id, 'name' => 'Poulet E2E'],
        $productPayload
    );

    Order::where('order_no', $orderNo)->delete();

    $order = Order::create([
        'order_no' => $orderNo,
        'user_id' => $users['customer']->id,
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driver->id,
        'product_id' => $product->id,
        'qty' => 1,
        'price' => 4500,
        'total_items' => 1,
        'offer_discount' => 0,
        'tax' => 0,
        'delivery_charges' => 500,
        'sub_total' => 4500,
        'total' => 5000,
        'admin_commission' => 0,
        'restaurant_commission' => 0,
        'driver_tip' => 0,
        'status' => 'assign',
        'business_status' => 'out_for_delivery',
        'technical_status' => null,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'delivery_address' => 'Adresse client E2E',
        'scheduled_date' => null,
        'd_lat' => '0',
        'd_lng' => '0',
        'ordered_time' => $now->copy()->subHour(),
        'delivered_time' => null,
        'latitude' => null,
        'longitude' => null,
    ]);

    Delivery::updateOrCreate(
        ['order_id' => $order->id],
        [
            'restaurant_id' => $restaurant->id,
            'driver_id' => $driver->id,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 500,
            'assigned_at' => $now->copy()->subMinutes(45),
            'picked_up_at' => $now->copy()->subMinutes(20),
            'delivery_otp_code' => $deliveryOtp,
            'delivery_otp_expires_at' => $now->copy()->addDay(),
            'otp_verified_at' => null,
            'incident_status' => null,
            'incident_reason' => null,
            'incident_notes' => null,
            'incident_reported_by' => null,
            'incident_reported_by_id' => null,
            'incident_reported_at' => null,
            'failed_attempts' => 0,
            'last_failed_attempt_at' => null,
            'customer_absent_at' => null,
            'redelivery_requested_at' => null,
            'support_status' => null,
            'support_notes' => null,
            'support_resolved_at' => null,
            'support_resolved_by' => null,
        ]
    );

    DB::table('restaurant_payments')->updateOrInsert(
        ['transaction_id' => 'E2E-PENDING-REST-20260327'],
        [
            'restaurant_id' => $restaurant->id,
            'payout_amount' => 12500,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    DB::table('driver_payments')->updateOrInsert(
        ['transaction_id' => 'E2E-PENDING-DRIVER-20260327'],
        [
            'driver_id' => $driver->id,
            'payout_amount' => 8400,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
});

echo json_encode([
    'seeded' => true,
    'base_url' => 'https://bantudelice.cg',
    'accounts' => [
        'admin' => [
            'email' => $credentials['admin']['email'],
            'password' => $credentials['admin']['password'],
        ],
        'restaurant' => [
            'email' => $credentials['restaurant']['email'],
            'password' => $credentials['restaurant']['password'],
        ],
        'driver' => [
            'email' => $credentials['driver']['email'],
            'password' => $credentials['driver']['password'],
        ],
        'customer' => [
            'email' => $credentials['customer']['email'],
            'password' => $credentials['customer']['password'],
        ],
    ],
    'artifacts' => [
        'restaurant_name' => 'E2E Bistro',
        'driver_name' => 'E2E Driver',
        'order_no' => $orderNo,
        'delivery_otp' => $deliveryOtp,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

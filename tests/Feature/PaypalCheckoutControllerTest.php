<?php

namespace Tests\Feature;

use App\Order;
use App\Payment;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PaypalCheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_paypal_start_redirects_to_provider_approval_url(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'paypal',
            'status' => 'PENDING',
            'amount' => 8500,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $checkoutService = Mockery::mock(CheckoutService::class);
        $checkoutService->shouldReceive('startCheckout')
            ->once()
            ->andReturn([
                'payment' => $payment,
                'requires_external_payment' => true,
                'payment_payload' => [
                    'redirect_url' => 'https://paypal.example/approve',
                ],
            ]);

        $paymentService = Mockery::mock(PaymentService::class);

        $this->app->instance(CheckoutService::class, $checkoutService);
        $this->app->instance(PaymentService::class, $paymentService);

        $response = $this->actingAs($user)->post('/paypal', [
            'fulfillment_mode' => 'delivery',
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
        ]);

        $response->assertRedirect('https://paypal.example/approve');
    }

    public function test_paypal_return_redirects_to_thanks_after_confirmation(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id, 'name' => 'PayPal Restaurant',
            'user_name' => 'paypal-restaurant', 'email' => 'paypal-restaurant@example.com',
            'password' => bcrypt('secret'), 'city' => 'Brazzaville', 'address' => 'Adresse',
            'phone' => '0600000001', 'description' => 'Test', 'services' => 'both',
            'service_charges' => 0, 'delivery_charges' => 500, 'tax' => 0,
            'admin_commission' => 15, 'approved' => 1, 'featured' => 0,
            'account_name' => 'PayPal Restaurant', 'account_number' => 'REST-PP-001',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId, 'name' => 'Plats',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'restaurant_id' => $restaurantId,
            'name' => 'Produit PayPal', 'price' => 3500, 'image' => 'placeholder.jpg',
            'discount_price' => 0, 'description' => 'Test', 'featured' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurantId,
            'product_id' => $productId,
            'qty' => 1,
            'price' => 3500,
            'total_items' => 1,
            'order_no' => 'PAYPAL-ORDER-001',
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => 3500,
            'total' => 3500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'payment_method' => 'paypal',
            'payment_status' => 'paid',
            'status' => 'pending',
            'ordered_time' => now(),
            'delivered_time' => null,
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_reference' => 'PAYPAL-PROVIDER-001',
            'status' => 'PENDING',
            'amount' => 3500,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $checkoutService = Mockery::mock(CheckoutService::class);
        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('finalizePayPalReturn')
            ->once()
            ->withArgs(function (Payment $actualPayment, array $query): bool {
                return $actualPayment->provider_reference === 'PAYPAL-PROVIDER-001'
                    && ($query['token'] ?? null) === 'PAYPAL-PROVIDER-001';
            })
            ->andReturn($payment->fresh());

        $this->app->instance(CheckoutService::class, $checkoutService);
        $this->app->instance(PaymentService::class, $paymentService);

        $response = $this->get('/checkout/paypal/return?payment_id=' . $payment->id . '&token=PAYPAL-PROVIDER-001');

        $response->assertRedirect(route('thanks', ['orderID' => 'PAYPAL-ORDER-001']));
    }

    public function test_paypal_cancel_marks_pending_payment_cancelled(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'paypal',
            'provider_reference' => 'PAYPAL-PROVIDER-002',
            'status' => 'PENDING',
            'amount' => 4200,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $checkoutService = Mockery::mock(CheckoutService::class);
        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('cancelExternalPayment')
            ->once()
            ->withArgs(function (Payment $actualPayment, array $context): bool {
                return $actualPayment->provider_reference === 'PAYPAL-PROVIDER-002'
                    && ($context['provider'] ?? null) === 'paypal';
            })
            ->andReturn($payment->fresh());

        $this->app->instance(CheckoutService::class, $checkoutService);
        $this->app->instance(PaymentService::class, $paymentService);

        $response = $this->get('/checkout/paypal/cancel?payment_id=' . $payment->id . '&token=PAYPAL-PROVIDER-002');

        $response->assertRedirect(route('cart.detail'));
    }
}

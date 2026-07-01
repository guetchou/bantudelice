<?php

namespace Tests\Feature;

use App\Category;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentAllocationService;
use App\Domain\Payment\Services\PaymentStateMachine;
use App\Order;
use App\Payment;
use App\Product;
use App\Restaurant;
use App\Services\PaymentReconciliationService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentBusinessKernelTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_statuses_are_normalized_without_hiding_unknowns(): void
    {
        $this->assertSame(PaymentStatus::PAID, PaymentStatus::fromRaw('SUCCESSFUL'));
        $this->assertSame(PaymentStatus::PROCESSING, PaymentStatus::fromRaw('AUTHORIZED'));
        $this->assertSame(PaymentStatus::REVERSED, PaymentStatus::fromRaw('REVERSAL'));
        $this->assertSame(PaymentStatus::DISPUTED, PaymentStatus::fromRaw('CHARGEBACK'));
        $this->assertSame(
            PaymentStatus::UNKNOWN,
            PaymentStatus::fromRaw('UNMAPPED_PROVIDER_STATE')
        );
    }

    public function test_paid_payment_cannot_be_downgraded_directly_to_failed(): void
    {
        $user = User::factory()->create(['phone' => '0600010001']);
        $payment = $this->createPayment($user, null, 'STATE-MACHINE-001', 1000);
        $stateMachine = app(PaymentStateMachine::class);

        $stateMachine->transition(
            $payment,
            PaymentStatus::PAID,
            [],
            'test_confirmation'
        );

        $this->expectException(\DomainException::class);
        $stateMachine->transition(
            $payment->fresh(),
            PaymentStatus::FAILED,
            [],
            'illegal_downgrade'
        );
    }

    public function test_multiple_confirmed_payments_fund_one_order_without_double_allocation(): void
    {
        $user = User::factory()->create(['phone' => '0600010002']);
        $order = $this->createOrder($user, 'BD-ALLOC-001', 1000);
        $stateMachine = app(PaymentStateMachine::class);
        $allocations = app(PaymentAllocationService::class);

        $first = $this->createPayment($user, $order, 'ALLOC-001-A', 600);
        $first = $stateMachine->transition(
            $first,
            PaymentStatus::PAID,
            [],
            'test_confirmation'
        );
        $firstResult = $allocations->allocateConfirmedPayment($first);

        $this->assertSame(600, $firstResult['allocated_amount']);
        $this->assertSame(400, $firstResult['remaining_amount']);
        $this->assertFalse($firstResult['fully_funded']);

        $second = $this->createPayment(
            $user,
            $order,
            'ALLOC-001-B',
            500,
            'airtel_money'
        );
        $second = $stateMachine->transition(
            $second,
            PaymentStatus::PAID,
            [],
            'test_confirmation'
        );
        $secondResult = $allocations->allocateConfirmedPayment($second);

        $this->assertSame(400, $secondResult['allocated_amount']);
        $this->assertSame(100, $secondResult['unallocated_amount']);
        $this->assertSame(1000, $secondResult['allocated_total']);
        $this->assertTrue($secondResult['fully_funded']);

        $reused = $allocations->allocateConfirmedPayment($second->fresh());
        $this->assertTrue($reused['reused']);
        $this->assertDatabaseCount('payment_allocations', 2);
        $this->assertSame(
            1000,
            (int) DB::table('payment_allocations')->sum('amount')
        );
    }

    public function test_unmapped_provider_status_moves_unresolved_payment_to_unknown(): void
    {
        $user = User::factory()->create(['phone' => '0600010003']);
        $payment = $this->createPayment($user, null, 'UNKNOWN-001', 2500);

        $service = new class extends PaymentReconciliationService {
            protected function getProviderStatus(Payment $payment): array
            {
                return [
                    'status' => 'PROVIDER_STATE_NOT_MAPPED',
                    'provider_status' => 'PROVIDER_STATE_NOT_MAPPED',
                    'data' => [],
                ];
            }
        };

        $result = $service->reconcile($payment);

        $this->assertFalse($result['reconciled']);
        $this->assertSame(
            'UNKNOWN',
            $result['status'],
            json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $this->assertSame('UNKNOWN', $payment->fresh()->status);
    }

    private function createPayment(
        User $user,
        ?Order $order,
        string $reference,
        int $amount,
        string $provider = 'momo'
    ): Payment {
        return Payment::create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'provider' => $provider,
            'provider_reference' => $reference,
            'status' => 'PENDING',
            'amount' => $amount,
            'currency' => 'XAF',
            'meta' => [],
        ]);
    }

    private function createOrder(User $user, string $orderNo, int $total): Order
    {
        $suffix = Str::lower(Str::random(8));
        $restaurant = new Restaurant();
        $restaurant->forceFill([
            'user_id' => $user->id,
            'name' => 'Restaurant test ' . $suffix,
            'email' => 'restaurant-' . $suffix . '@example.test',
            'password' => bcrypt('test-password'),
            'city' => 'Brazzaville',
            'address' => 'Adresse test',
            'phone' => '0600020002',
            'user_name' => 'restaurant_' . $suffix,
            'latitude' => '0',
            'longitude' => '0',
            'min_order' => 0,
            'avg_delivery_time' => 30,
            'services' => 'delivery,pickup',
            'service_charges' => 0,
            'delivery_charges' => 0,
            'tax' => 0,
            'admin_commission' => 0,
            'account_name' => 'Compte test',
            'account_number' => '0000000000',
        ]);
        $restaurant->save();

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Catégorie test',
            'is_available' => 1,
            'sort_order' => 1,
        ]);
        $product = Product::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Produit test',
            'image' => 'test-product.jpg',
            'price' => $total,
            'discount_price' => 0,
            'description' => 'Produit de test métier paiement',
            'is_available' => 1,
            'sort_order' => 1,
            'featured' => 0,
        ]);

        return Order::create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $total,
            'total_items' => 1,
            'latitude' => '0',
            'longitude' => '0',
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => $total,
            'total' => $total,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'delivery_address' => 'Adresse test',
            'order_no' => $orderNo,
            'd_lat' => '0',
            'd_lng' => '0',
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'status' => 'pending',
            'business_status' => 'accepted_awaiting_payment',
            'fulfillment_mode' => 'pickup',
            'ordered_time' => now(),
        ]);
    }
}

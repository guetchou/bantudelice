<?php

namespace Tests\Feature\Order;

use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\ConfirmReceiptRequest;
use App\Http\Requests\Order\ReportIncidentRequest;
use App\Http\Requests\Order\RequestRedeliveryRequest;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestsTest extends TestCase
{
    // ── PlaceOrderRequest ──────────────────────────────────────────

    public function test_place_order_requires_fulfillment_mode(): void
    {
        $validator = Validator::make(
            ['payment_method' => 'cash'],
            (new PlaceOrderRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('fulfillment_mode', $validator->errors()->toArray());
    }

    public function test_place_order_requires_payment_method(): void
    {
        $validator = Validator::make(
            ['fulfillment_mode' => 'delivery'],
            (new PlaceOrderRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_method', $validator->errors()->toArray());
    }

    public function test_place_order_rejects_invalid_payment_method(): void
    {
        $validator = Validator::make(
            ['fulfillment_mode' => 'delivery', 'payment_method' => 'bitcoin'],
            (new PlaceOrderRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_method', $validator->errors()->toArray());
    }

    public function test_place_order_rejects_invalid_fulfillment_mode(): void
    {
        $validator = Validator::make(
            ['fulfillment_mode' => 'drone', 'payment_method' => 'cash'],
            (new PlaceOrderRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('fulfillment_mode', $validator->errors()->toArray());
    }

    public function test_place_order_passes_with_minimum_valid_data(): void
    {
        $validator = Validator::make(
            ['fulfillment_mode' => 'pickup', 'payment_method' => 'cash'],
            (new PlaceOrderRequest())->rules()
        );
        $this->assertFalse($validator->fails());
    }

    // ── ConfirmReceiptRequest ──────────────────────────────────────

    public function test_confirm_receipt_otp_max_12_chars(): void
    {
        $validator = Validator::make(
            ['delivery_otp' => str_repeat('x', 13)],
            (new ConfirmReceiptRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('delivery_otp', $validator->errors()->toArray());
    }

    public function test_confirm_receipt_passes_with_valid_otp(): void
    {
        $validator = Validator::make(
            ['delivery_otp' => '123456'],
            (new ConfirmReceiptRequest())->rules()
        );
        $this->assertFalse($validator->fails());
    }

    // ── ReportIncidentRequest ──────────────────────────────────────

    public function test_incident_requires_reason(): void
    {
        $validator = Validator::make([], (new ReportIncidentRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('reason', $validator->errors()->toArray());
    }

    public function test_incident_rejects_unknown_reason(): void
    {
        $validator = Validator::make(
            ['reason' => 'alien_abduction'],
            (new ReportIncidentRequest())->rules()
        );
        $this->assertTrue($validator->fails());
    }

    public function test_incident_passes_with_valid_reason(): void
    {
        $validator = Validator::make(
            ['reason' => 'missing_items'],
            (new ReportIncidentRequest())->rules()
        );
        $this->assertFalse($validator->fails());
    }

    // ── AddToCartRequest ───────────────────────────────────────────

    public function test_add_to_cart_requires_product_id_and_qty(): void
    {
        $validator = Validator::make([], (new AddToCartRequest())->rules());
        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('product_id', $errors);
        $this->assertArrayHasKey('qty', $errors);
    }

    public function test_add_to_cart_rejects_qty_above_99(): void
    {
        $validator = Validator::make(
            ['product_id' => 1, 'qty' => 100],
            (new AddToCartRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('qty', $validator->errors()->toArray());
    }

    public function test_add_to_cart_rejects_zero_qty(): void
    {
        $validator = Validator::make(
            ['product_id' => 1, 'qty' => 0],
            (new AddToCartRequest())->rules()
        );
        $this->assertTrue($validator->fails());
    }

    // ── RequestRedeliveryRequest ───────────────────────────────────

    public function test_redelivery_notes_are_optional(): void
    {
        $validator = Validator::make([], (new RequestRedeliveryRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_redelivery_notes_max_1000_chars(): void
    {
        $validator = Validator::make(
            ['notes' => str_repeat('a', 1001)],
            (new RequestRedeliveryRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }
}

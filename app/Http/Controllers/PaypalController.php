<?php

namespace App\Http\Controllers;

use App\Payment;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaypalController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected PaymentService $paymentService
    ) {}

    public function index()
    {
        return view('paywithpaypal');
    }

    public function payWithpaypal(Request $request): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter');
        }

        $request->validate([
            'fulfillment_mode' => 'nullable|in:delivery,pickup',
            'delivery_address' => 'nullable|string|max:500',
            'd_lat' => 'nullable',
            'd_lng' => 'nullable',
            'driver_tip' => 'nullable|numeric|min:0',
            'voucher_code' => 'nullable|string',
            'scheduled_date' => 'nullable|date|after:now',
            'pickup_note' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->checkoutService->startCheckout(
                $request->user(),
                'paypal',
                $this->buildCheckoutData($request)
            );

            $payment = $result['payment'];
            $redirectUrl = trim((string) data_get($result, 'payment_payload.redirect_url', ''));

            if ($redirectUrl !== '') {
                return redirect()->away($redirectUrl);
            }

            $confirmedPayment = $this->paymentService->finalizePayPalReturn($payment, [
                'demo' => '1',
                'source' => 'paypal_controller_fallback',
            ]);

            return $this->redirectToThanks($confirmedPayment, 'Paiement PayPal confirmé.');
        } catch (\Throwable $e) {
            Log::error('Erreur lancement checkout PayPal', [
                'user_id' => optional($request->user())->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('message', 'Impossible d’initier le paiement PayPal pour le moment.');
        }
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $payment = $this->resolvePaymentFromRequest($request);
        if (! $payment) {
            return redirect()->route('cart.detail')->with('message', 'Paiement PayPal introuvable.');
        }

        try {
            $payment = $this->paymentService->finalizePayPalReturn($payment, $request->query());

            return $this->redirectToThanks($payment, 'Paiement PayPal confirmé.');
        } catch (\Throwable $e) {
            Log::error('Erreur retour PayPal', [
                'payment_id' => $payment->id,
                'provider_reference' => $payment->provider_reference,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('cart.detail')->with('message', 'Le paiement PayPal n’a pas pu être confirmé.');
        }
    }

    public function handleCancel(Request $request): RedirectResponse
    {
        $payment = $this->resolvePaymentFromRequest($request);
        if ($payment) {
            $this->paymentService->cancelExternalPayment($payment, [
                'provider' => 'paypal',
                'query' => $request->query(),
            ]);
        }

        return redirect()->route('cart.detail')->with('message', 'Paiement PayPal annulé.');
    }

    public function getPaymentStatus(Request $request): RedirectResponse
    {
        return $request->boolean('cancelled')
            ? $this->handleCancel($request)
            : $this->handleReturn($request);
    }

    private function buildCheckoutData(Request $request): array
    {
        return [
            'fulfillment_mode' => strtolower((string) $request->input('fulfillment_mode', 'delivery')) === 'pickup' ? 'pickup' : 'delivery',
            'delivery_address' => trim((string) $request->input('delivery_address', '')),
            'd_lat' => $request->input('d_lat'),
            'd_lng' => $request->input('d_lng'),
            'driver_tip' => $request->input('driver_tip', 0),
            'voucher_code' => $request->input('voucher_code'),
            'scheduled_date' => $request->input('scheduled_date'),
            'pickup_note' => $request->input('pickup_note'),
        ];
    }

    private function resolvePaymentFromRequest(Request $request): ?Payment
    {
        $paymentId = (int) $request->query('payment_id', 0);
        $providerReference = trim((string) $request->query('token', ''));

        if ($paymentId > 0) {
            $payment = Payment::find($paymentId);
            if (
                $payment
                && $providerReference !== ''
                && ! empty($payment->provider_reference)
                && $payment->provider_reference !== $providerReference
            ) {
                return null;
            }

            return $payment;
        }

        if ($providerReference !== '') {
            return Payment::where('provider', 'paypal')
                ->where('provider_reference', $providerReference)
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function redirectToThanks(Payment $payment, string $successMessage): RedirectResponse
    {
        $order = $payment->order()->latest('id')->first();
        if (! $order) {
            return redirect()->route('cart.detail')->with('message', 'Paiement confirmé, mais commande introuvable.');
        }

        return redirect()->route('thanks', ['orderID' => $order->order_no])->with('success', $successMessage);
    }
}

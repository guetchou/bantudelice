<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // /checkout/order utilise l'ancien contrôleur qui créait une livraison avant
        // l'acceptation du restaurant. Le checkout actif passe exclusivement par
        // POST /checkout/api et CheckoutService.
        return ! $this->routeIs('place.order');
    }

    public function rules(): array
    {
        $isMomo = $this->input('payment_method') === 'mobile_money';

        return [
            'fulfillment_mode'           => 'required|in:delivery,pickup',
            'payment_method'             => 'required|in:cash,mobile_money,paypal',
            'phone'                      => [$isMomo ? 'required' : 'nullable', 'string', 'max:30'],
            'delivery_address'           => 'nullable|string|max:500',
            'delivery_area'              => 'nullable|string|max:120',
            'delivery_city'              => 'nullable|string|max:120',
            'delivery_department'        => 'nullable|string|max:120',
            'd_lat'                      => 'nullable|numeric',
            'd_lng'                      => 'nullable|numeric',
            'delivery_address_confirmed' => 'nullable|boolean',
            'driver_tip'                 => 'nullable|numeric|min:0',
            'voucher_code'               => 'nullable|string|max:50',
            'scheduled_date'             => 'nullable|date|after:now',
            'address_id'                 => 'nullable|integer',
            'pickup_note'                => 'nullable|string|max:500',
            'use_loyalty_points'         => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro Mobile Money est obligatoire pour ce mode de paiement.',
            'delivery_address.required_without' => "L'adresse de livraison est obligatoire.",
        ];
    }
}

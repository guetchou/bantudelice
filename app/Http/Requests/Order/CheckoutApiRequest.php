<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'payment_method'             => 'required|string|in:cash,momo,paypal',
            'fulfillment_mode'           => 'nullable|string|in:delivery,pickup',
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
            'address_id'                 => 'nullable|integer|exists:user_address,id',
            'pickup_note'                => 'nullable|string|max:500',
            'phone'                      => 'nullable|string|max:30',
        ];
    }
}

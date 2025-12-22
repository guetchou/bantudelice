<?php

namespace App\Http\Requests\Colis;

use Illuminate\Foundation\Http\FormRequest;

class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weight_kg' => 'required|numeric|min:0.1',
            'service_level' => 'required|string|in:standard,express',
            'declared_value' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'pickup_type' => 'nullable|string|in:door,relay',
            'dropoff_type' => 'nullable|string|in:door,relay',
            
            'pickup_address' => 'required|array',
            'pickup_address.full_name' => 'required|string',
            'pickup_address.phone' => 'required|string',
            'pickup_address.city' => 'required|string',
            'pickup_address.district' => 'required|string',
            'pickup_address.address_line' => 'required|string',
            'pickup_address.landmark' => 'nullable|string',
            
            'dropoff_address' => 'required|array',
            'dropoff_address.full_name' => 'required|string',
            'dropoff_address.phone' => 'required|string',
            'dropoff_address.city' => 'required|string',
            'dropoff_address.district' => 'required|string',
            'dropoff_address.address_line' => 'required|string',
            'dropoff_address.landmark' => 'nullable|string',
        ];
    }
}

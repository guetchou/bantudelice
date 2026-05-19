<?php

namespace App\Http\Requests\Colis;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public or any authenticated user
    }

    public function rules(): array
    {
        return [
            'weight_kg' => 'required|numeric|min:0.1',
            'service_level' => 'required|string|in:standard,express',
            'declared_value' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'delivery_otp'       => 'nullable|string|max:12',
            'customer_confirmed' => 'nullable|boolean',
        ];
    }
}

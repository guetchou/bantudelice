<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // panier accessible avant auth
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|min:1',
            'qty'        => 'required|integer|min:1|max:99',
        ];
    }

    public function messages(): array
    {
        return [
            'qty.max' => 'Quantité maximale : 99.',
        ];
    }
}

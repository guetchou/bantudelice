<?php

namespace App\Http\Requests\Colis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourierEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(['picked_up', 'in_transit', 'at_relay', 'out_for_delivery', 'failed', 'returned', 'damaged', 'lost']),
            ],
            'notes' => 'nullable|string',
            'meta' => 'nullable|array',
        ];
    }
}

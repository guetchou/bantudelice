<?php

namespace App\Http\Requests\Colis;

use Illuminate\Foundation\Http\FormRequest;

class CourierEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'meta' => 'nullable|array',
        ];
    }
}

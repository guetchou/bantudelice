<?php

namespace App\Http\Requests\Colis;

use Illuminate\Foundation\Http\FormRequest;

class DeliverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => 'nullable|string|size:6',
            'type' => 'nullable|in:photo,signature',
            'file' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }
}

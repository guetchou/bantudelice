<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        $userId = auth('api')->id();

        return [
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:30|regex:/^(\+?[0-9]{7,15})$/|unique:users,phone,' . $userId,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex'  => 'Format de numéro invalide.',
            'email.unique' => 'Cet e-mail est déjà utilisé.',
            'phone.unique' => 'Ce numéro est déjà utilisé.',
        ];
    }
}

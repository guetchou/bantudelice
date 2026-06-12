<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'phone'    => 'required|string|max:30|unique:users,phone',
            'password' => 'required|string|min:6',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cet e-mail est déjà utilisé.',
            'phone.unique' => 'Ce numéro est déjà utilisé.',
            'password.min' => 'Le mot de passe doit faire au moins 6 caractères.',
        ];
    }
}

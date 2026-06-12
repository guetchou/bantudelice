<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ReportIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|in:late_delivery,missing_items,courier_issue,delivery_issue|max:100',
            'notes'  => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Veuillez préciser le motif de l\'incident.',
            'reason.in'       => 'Motif invalide.',
        ];
    }
}

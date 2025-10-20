<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'output' => 'required|string|max:255',
            'outputRef' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'outcome_id.required' => 'Outcome selection is required.',
            'outcome_id.exists' => 'The selected outcome is invalid.',
            'output.required' => 'The output field is required.',
            'outputRef.required' => 'The output reference field is required.',
        ];
    }

}

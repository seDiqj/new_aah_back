<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'         => 'required|exists:projects,id',
            'outcomes'           => 'required|array|min:1',
            'outcomes.*.outcome'    => 'required|string|max:255',
            'outcomes.*.outcomeRef' => 'required|string|max:255|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required'        => 'Project ID is required.',
            'project_id.exists'          => 'The specified project does not exist.',
            'outcomes.required'          => 'At least one outcome is required.',
            'outcomes.*.outcome.required'    => 'Outcome field is required.',
            'outcomes.*.outcomeRef.required' => 'Outcome reference is required.',
            'outcomes.*.outcomeRef.distinct' => 'Outcome references must be unique in the array.',
        ];
    }
}

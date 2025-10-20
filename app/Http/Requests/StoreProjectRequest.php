<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'projectCode'      => 'required|string|max:255|unique:projects,projectCode',
            'projectTitle'     => 'required|string|max:255',
            'projectGoal'      => 'required|string|max:255',
            'projectDonor'     => 'required|string|max:255',
            'startDate'        => 'required|date',
            'endDate'          => 'required|date|after_or_equal:startDate',
            'status'           => 'required|in:planed,ongoing,completed,onhold,canclled',
            'projectManager'   => 'required|string|max:255',
            'reportingDate'    => 'required|string|max:255',
            'reportingPeriod'  => 'required|string|max:255',
            'description'      => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'projectCode.required' => 'Project code is required',
            'projectCode.unique'   => 'Project code must be unique',
            'endDate.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }
}

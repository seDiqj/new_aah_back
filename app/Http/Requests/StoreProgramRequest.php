<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'projectCode'            => 'required|exists:projects,projectCode',
            'focalPoint'           => 'required|string|max:255',
            'province'           => 'required|exists:provinces,name',
            'district'           => 'required|exists:districts,name',
            'village'               => 'required|string|max:255',
            'siteCode'             => 'required|string|max:255',
            'healthFacilityName'  => 'required|string|max:255',
            'interventionModality' => 'required|string|max:255',
        ];
    }
}
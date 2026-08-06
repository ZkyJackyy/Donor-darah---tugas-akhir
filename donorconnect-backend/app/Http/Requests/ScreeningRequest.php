<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'donor_candidate_id' => 'required|integer|exists:donor_candidates,id',
            'health_status' => 'required|boolean',
            'min_weight' => 'required|boolean',
            'no_medicine' => 'required|boolean',
            'not_pregnant' => 'required|boolean',
        ];
    }
}

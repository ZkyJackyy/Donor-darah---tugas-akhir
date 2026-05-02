<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'donor_candidate_id' => 'required|exists:donor_candidates,id',
            'status' => 'required|in:confirmed,declined',
        ];
    }
}

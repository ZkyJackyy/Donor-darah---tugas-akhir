<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'donor_candidate_id' => [
                'required',
                Rule::exists('donor_candidates', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'status' => 'required|in:confirmed,declined',
        ];
    }

    public function messages(): array
    {
        return [
            'donor_candidate_id.exists' => 'Kandidat donor tidak ditemukan atau bukan milik Anda.',
        ];
    }
}

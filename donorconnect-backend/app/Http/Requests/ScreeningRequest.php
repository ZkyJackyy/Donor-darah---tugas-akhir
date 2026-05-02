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
            'health_status' => 'required|boolean|accepted',
            'min_weight' => 'required|boolean|accepted',
            'no_medicine' => 'required|boolean|accepted',
            'not_pregnant' => 'required|boolean|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'health_status.accepted' => 'Anda harus mengkonfirmasi kondisi tubuh sehat',
            'min_weight.accepted' => 'Anda harus mengkonfirmasi berat badan minimal 45 kg',
            'no_medicine.accepted' => 'Anda harus mengkonfirmasi tidak sedang mengonsumsi obat tertentu',
            'not_pregnant.accepted' => 'Anda harus mengkonfirmasi tidak sedang hamil',
        ];
    }
}

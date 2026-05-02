<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DonorScreening extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_candidate_id',
        'health_status',
        'min_weight',
        'no_medicine',
        'not_pregnant',
        'screened_at',
    ];

    protected function casts(): array
    {
        return [
            'health_status' => 'boolean',
            'min_weight' => 'boolean',
            'no_medicine' => 'boolean',
            'not_pregnant' => 'boolean',
            'screened_at' => 'datetime',
        ];
    }

    public function donorCandidate()
    {
        return $this->belongsTo(DonorCandidate::class);
    }
}

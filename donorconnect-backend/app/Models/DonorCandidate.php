<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonorCandidate extends Model
{
    /** @use HasFactory<\Database\Factories\DonorCandidateFactory> */
    use HasFactory;

    protected $fillable = [
        'blood_request_id',
        'user_id',
        'distance_km',
        'status',
        'notified_at',
        'confirmed_at',
        'verified_at',
        'verification_method',
        'qr_token',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'notified_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function bloodRequest()
    {
        return $this->belongsTo(BloodRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function screening()
    {
        return $this->hasOne(DonorScreening::class);
    }
}

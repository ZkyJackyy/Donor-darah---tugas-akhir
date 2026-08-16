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
        'expires_at',
        'verified_at',
        'verification_method',
        'kode_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'notified_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public static function generateVerificationCode(): string
    {
        $attempts = 0;
        $maxAttempts = 10;
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
            $attempts++;
        } while (static::where('kode_verifikasi', $code)->exists() && $attempts < $maxAttempts);
        
        if ($attempts >= $maxAttempts) {
            // Jika collision terlalu parah, tambahkan karakter unik berbasis timestamp
            $code = substr($code, 0, 4) . substr(strtoupper(uniqid()), -2);
        }
        
        return $code;
    }
}

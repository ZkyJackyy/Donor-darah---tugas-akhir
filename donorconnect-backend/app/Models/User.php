<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'photo',
        'birth_date',
        'weight',
        'blood_type',
        'rhesus',
        'last_donor_date',
        'latitude',
        'longitude',
        'fcm_token',
        'is_available',
        'role',
        'email_verification_code',
        'email_verification_code_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'last_donor_date' => 'date',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_available' => 'boolean',
            'weight' => 'decimal:2',
        ];
    }

    public function bloodRequests()
    {
        return $this->hasMany(BloodRequest::class, 'admin_id');
    }

    public function donorCandidates()
    {
        return $this->hasMany(DonorCandidate::class);
    }

    public function donorHistories()
    {
        return $this->hasMany(DonorHistory::class);
    }

    public function waLogs()
    {
        return $this->hasMany(WaLog::class, 'phone', 'phone');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodRequest extends Model
{
    /** @use HasFactory<\Database\Factories\BloodRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'blood_type',
        'rhesus',
        'urgency_level',
        'hospital_name',
        'hospital_address',
        'latitude',
        'longitude',
        'required_bags',
        'deadline',
        'status',
        'notes',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function donorCandidates()
    {
        return $this->hasMany(DonorCandidate::class);
    }
}

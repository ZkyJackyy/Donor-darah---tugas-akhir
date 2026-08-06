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
        'requested_by_user_id',
        'type',
        'event_starts_at',
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
        'patient_name',
        'patient_relationship',
        'rejection_reason',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'event_starts_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function donorCandidates()
    {
        return $this->hasMany(DonorCandidate::class);
    }

    public function isEvent(): bool
    {
        return $this->type === 'event';
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }

    /**
     * Transisi otomatis ke 'fulfilled' begitu jumlah kandidat yang benar-benar
     * verified (bukan sekadar confirmed) sudah memenuhi required_bags.
     *
     * Event terbuka tidak punya kuota keras (required_bags cuma target
     * informatif), jadi tidak pernah auto-fulfilled — admin tutup manual.
     */
    public function checkAndAutoFulfill(): void
    {
        if ($this->status !== 'open' || $this->isEvent() || $this->required_bags === null) {
            return;
        }

        $verifiedCount = $this->donorCandidates()->where('status', 'verified')->count();

        if ($verifiedCount >= $this->required_bags) {
            $this->update(['status' => 'fulfilled']);
        }
    }
}

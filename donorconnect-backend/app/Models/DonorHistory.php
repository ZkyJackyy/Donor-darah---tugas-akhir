<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonorHistory extends Model
{
    /** @use HasFactory<\Database\Factories\DonorHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blood_request_id',
        'donor_date',
        'location_name',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'donor_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bloodRequest()
    {
        return $this->belongsTo(BloodRequest::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

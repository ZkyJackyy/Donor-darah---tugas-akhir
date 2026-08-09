<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'message',
        'blood_request_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function bloodRequest()
    {
        return $this->belongsTo(BloodRequest::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaLog extends Model
{
    protected $fillable = [
        'user_id',
        'blood_request_id',
        'phone',
        'message',
        'status',
        'error_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bloodRequest()
    {
        return $this->belongsTo(BloodRequest::class);
    }
}

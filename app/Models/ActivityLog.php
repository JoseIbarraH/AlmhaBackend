<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    protected $fillable = [
        'user_id',
        'model_type',
        'model_id',
        'action',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}

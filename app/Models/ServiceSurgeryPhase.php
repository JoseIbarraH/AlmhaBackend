<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSurgeryPhase extends Model
{
    use HasFactory;

    protected $table = 'service_surgery_phases';

    protected $fillable = [
        'service_id',
        'recovery_time',
        'preoperative_recommendations',
        'postoperative_recommendations',
        'lang'
    ];

    protected $casts = [
        'recovery_time' => 'array',
        'preoperative_recommendations' => 'array',
        'postoperative_recommendations' => 'array',
    ];

    public $timestamps = false;

    protected $touches = ['service'];

    // Relación inversa
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

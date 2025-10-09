<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTranslation extends Model
{
    protected $fillable = [
        'service_id',
        'description',
        'title',
        'lang',
    ];

    protected $touches = ['service'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
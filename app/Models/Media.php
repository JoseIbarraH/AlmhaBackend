<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'type',
        'media_type',
        'path',
        'title',
        'order',
    ];

    // Relación polimórfica
    public function model()
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceFaq extends Model
{
    use HasFactory;

    protected $table = 'service_faqs';

    protected $fillable = [
        'service_id',
        'question',
        'answer',
        'lang'
    ];

    public $timestamps = false;

    protected $touches = ['service'];

    // Relación inversa
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

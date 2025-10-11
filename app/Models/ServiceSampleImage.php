<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSampleImage extends Model
{
    use HasFactory;

    protected $table = 'service_sample_images';

    protected $fillable = [
        'service_id',
        'technique',
        'recovery',
        'postoperative_care'
    ];

    protected $touches = ['service'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ServiceSampleImage extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    protected $table = 'service_sample_images';

    protected $fillable = [
        'service_id',
        'technique',
        'recovery',
        'postoperative_care'
    ];

    public $timestamps = false;

    protected $touches = ['service'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

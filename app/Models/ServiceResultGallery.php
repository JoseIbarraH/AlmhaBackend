<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ServiceResultGallery extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'service_result_galleries';

    protected $fillable = [
        'service_id',
        'path',
    ];

    public $timestamps = false;

    protected $touches = ['service'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

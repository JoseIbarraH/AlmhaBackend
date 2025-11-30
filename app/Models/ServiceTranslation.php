<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ServiceTranslation extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    protected $table = 'service_translations';

    protected $fillable = [
        'service_id',
        'description',
        'title',
        'lang',
    ];

    public $timestamps = false;

    protected $touches = ['service'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

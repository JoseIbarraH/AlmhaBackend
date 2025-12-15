<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'user_id',
        'image',
        'slug',
        'status',
        'view'
    ];

    protected $appends = ['full_path'];

    /**
     * Relaciones
     */
    public function serviceTranslation()
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    public function surgeryPhases()
    {
        return $this->hasMany(ServiceSurgeryPhase::class);
    }

    public function frequentlyAskedQuestions()
    {
        return $this->hasMany(ServiceFaq::class);
    }

    public function sampleImages()
    {
        return $this->hasOne(ServiceSampleImage::class, 'service_id');
    }

    public function resultGallery()
    {
        return $this->hasMany(ServiceResultGallery::class, 'service_id');
    }

    public function getFullPathAttribute()
    {
        if (!$this->path) {
            return null;
        }
        return url('/storage/' . $this->path);
    }
}

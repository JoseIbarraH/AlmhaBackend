<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Relaciones
     */
    public function translation($lang = null)
    {
        $locale = $lang ?? app()->getLocale();
        return $this->hasOne(ServiceTranslation::class)->where('lang', $locale);
    }

    public function frequentlyAskedQuestions($lang = null)
    {
        $locale = $lang ?? app()->getLocale();
        return $this->hasMany(ServiceFaq::class)->where('lang', $locale);
    }

    public function surgeryPhases($lang = null)
    {
        $locale = $lang ?? app()->getLocale();
        return $this->hasMany(ServiceSurgeryPhase::class)->where('lang', $locale);
    }

    public function sampleImages()
    {
        return $this->hasOne(ServiceSampleImage::class, 'service_id');
    }

    public function resultGallery()
    {
        return $this->hasMany(ServiceResultGallery::class, 'service_id');
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => match (true) {
                empty($value) => null,
                str_starts_with($value, 'http') => $value,
                default => asset("storage/{$value}"),
            },
        );
    }

    public function scopeRelationTitle($query, $value)
    {
        return $query->whereHas('translation', function ($q) use ($value) {
            $q->where('title', 'like', "%{$value}%");
        });
    }
}

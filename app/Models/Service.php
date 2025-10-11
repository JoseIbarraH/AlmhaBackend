<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_image',
        'status'
    ];

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

    protected static function booted()
    {
        static::deleting(function ($service) {
            $path = "images/service/{$service->id}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->deleteDirectory($path);
            }
        });
    }

}

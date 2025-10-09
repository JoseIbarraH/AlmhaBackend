<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Service extends Model
{
    use HasFactory;

    protected $fillable = [
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
        return $this->hasMany(ServiceSampleImage::class, 'service_id');
    }

    public function resultGallery()
    {
        return $this->hasMany(ServiceResultGallery::class, 'service_id');
    }






}
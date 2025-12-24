<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;

class Procedure extends Model implements Auditable
{
    use SoftDeletes, HasFactory, HasSlug;
    use \OwenIt\Auditing\Auditable;

    protected $table = "procedures";
    protected $fillable = ['slug', 'image', 'status', 'views'];

    protected $casts = [
        'views' => 'integer',
    ];

    // Relación con traducciones
    public function translations()
    {
        return $this->hasMany(ProcedureTranslation::class);
    }

    // Traducción actual según el idioma
    public function translation()
    {
        return $this->hasOne(ProcedureTranslation::class)
            ->where('lang', app()->getLocale());
    }

    // Secciones
    public function sections()
    {
        return $this->hasMany(ProcedureSection::class)->orderBy('order');
    }

    // Pasos de preparación
    public function preparationSteps()
    {
        return $this->hasMany(ProcedurePreparationStep::class)->orderBy('order');
    }

    // Fases de recuperación
    public function recoveryPhases()
    {
        return $this->hasMany(ProcedureRecoveryPhase::class)->orderBy('order');
    }

    // Instrucciones postoperatorias
    public function postoperativeInstructions()
    {
        return $this->hasMany(ProcedurePostoperativeInstruction::class)->orderBy('order');
    }

    // Solo instrucciones "SÍ debes hacer"
    public function postoperativeDos()
    {
        return $this->postoperativeInstructions()->where('type', 'do');
    }

    // Solo instrucciones "NO debes hacer"
    public function postoperativeDonts()
    {
        return $this->postoperativeInstructions()->where('type', 'dont');
    }

    // Preguntas frecuentes
    public function faqs()
    {
        return $this->hasMany(ProcedureFaq::class)->orderBy('order');
    }

    public function resultGallery()
    {
        return $this->hasMany(ProcedureResultGallery::class);
    }

    // Incrementar vistas
    public function incrementViews()
    {
        $this->increment('views');
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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function ($model) {
                $en = $model->translations('en')->first();
                return $en ? $en->title : '';
            })
            ->saveSlugsTo('slug');
    }
}

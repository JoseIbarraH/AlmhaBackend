<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $table = 'blog_categories';

    protected $fillable = [
        'code',
    ];

    /**
     * Relación: Una categoría tiene muchas traducciones
     */
    public function translations()
    {
        return $this->hasMany(BlogCategoryTranslation::class, 'category_id');
    }

    /**
     * Obtener la traducción según el idioma actual
     */
    public function translation($lang = null)
    {
        $lang = $lang ?? app()->getLocale();
        return $this->translations()->where('lang', $lang)->first();
    }

    /**
     * Accesor para ->name (devuelve el nombre traducido automáticamente)
     */
    public function getNameAttribute()
    {
        $translation = $this->translation();
        return $translation?->name ?? $this->code;
    }
}

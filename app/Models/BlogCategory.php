<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $table = 'blog_categories';

    protected $fillable = [
        'code',
    ];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_id');
    }


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

    public function translationRelation()
    {
        return $this->hasOne(BlogCategoryTranslation::class, 'category_id')
            ->where('lang', app()->getLocale());
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

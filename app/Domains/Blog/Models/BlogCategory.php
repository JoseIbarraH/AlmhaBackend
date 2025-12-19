<?php

namespace App\Domains\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BlogCategory extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'blog_categories';

    protected $fillable = [
        'code',
    ];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_id');
    }

    /**
     * Obtener la traducción según el idioma actual
     */
    public function translation($lang = null)
    {
        $locale = $lang ?? app()->getLocale();
        return $this->hasOne(BlogCategoryTranslation::class, 'category_id')->where('lang', $locale);
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

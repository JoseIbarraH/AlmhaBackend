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

    public $timestamps = false;

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_id');
    }

    /**
     * Obtener la traducción según el idioma actual
     */
    public function translation()
    {
        return $this->hasOne(BlogCategoryTranslation::class, 'category_id')->where('lang', app()->getLocale());
    }

    public function translations()
    {
        return $this->hasMany(BlogCategoryTranslation::class, 'category_id');
    }

    public function scopeRelationTitle($query, $value)
    {
        return $query->whereHas('translation', function ($q) use ($value) {
            $q->where('title', 'like', "%{$value}%");
        });
    }
}

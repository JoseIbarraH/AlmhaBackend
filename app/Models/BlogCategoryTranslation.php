<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategoryTranslation extends Model
{
    protected $table = 'blog_category_translations';

    protected $fillable = [
        'category_id',
        'lang',
        'title'
    ];

    /**
     * Relación: Una traducción pertenece a una categoría
     */
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }
}

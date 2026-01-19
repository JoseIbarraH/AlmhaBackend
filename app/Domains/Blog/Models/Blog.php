<?php

namespace App\Domains\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\BlogFactory;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;

class Blog extends Model implements Auditable
{
    use SoftDeletes, HasFactory, HasSlug;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'blogs';
    protected $fillable = [
        'user_id',
        'slug',
        'image',
        'category_code',
        'writer',
        'views',
        'status',
    ];

    /**
     * Relaciones
     */
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_code', 'code');
    }

    /**
     * Traduccion a español
     */
    public function translation($lang = null)
    {
        $locale = $lang ?? app()->getLocale();
        return $this->hasOne(BlogTranslation::class)->where('lang', $locale);
    }

    public function translations()
    {
        return $this->hasMany(BlogTranslation::class, 'blog_id');
    }

    /**
     * Url de la imagen completa
     */
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
                $en = $model->translation('en')->first();
                return $en ? $en->title : '';
            })
            ->saveSlugsTo('slug');
    }

    public function scopeRelationTitle($query, $value)
    {
        return $query->whereHas('translation', function ($q) use ($value) {
            $q->where('title', 'like', "%{$value}%");
        });
    }

    protected static function newFactory()
    {
        return BlogFactory::new();
    }
}

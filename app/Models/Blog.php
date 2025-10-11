<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Blog extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'image',
        'category',
        'writer',
        'view',
        'status',
    ];

    public function blogTranslations()
    {
        return $this->hasMany(BlogTranslation::class, 'blog_id');
    }

    public function blogTranslation()
    {
        return $this->hasOne(BlogTranslation::class, 'blog_id')
                    ->where('lang', app()->getLocale());
    }

    protected static function booted()
    {
        static::deleting(function ($blog) {
            $path = "images/blog/{$blog->id}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->deleteDirectory($path);
            }
        });
    }
}

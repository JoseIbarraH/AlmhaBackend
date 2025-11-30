<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;

class Blog extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'blogs';
    protected $fillable = [
        'user_id',
        'slug',
        'image',
        'category_id',
        'writer',
        'view',
        'status',
    ];

    /**
     * Relaciones
     */
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function translations()
    {
        return $this->hasMany(BlogTranslation::class, 'blog_id');
    }

    public function translation()
    {
        return $this->hasOne(BlogTranslation::class, 'blog_id');
    }

    protected static function booted()
    {
        parent::boot();

        static::deleting(function ($model) {
            $path = "images/blog/{$model->id}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->deleteDirectory($path);
            }
        });
    }
}

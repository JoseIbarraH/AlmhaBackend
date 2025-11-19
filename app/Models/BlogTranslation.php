<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogTranslation extends Model
{
    use HasFactory;

    protected $table = 'blog_translations';
    protected $fillable = [
        'blog_id',
        'lang',
        'title',
        'content'
    ];

    public $timestamps = false;

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}

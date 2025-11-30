<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BlogTranslation extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;
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

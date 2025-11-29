<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignItem extends Model
{

    protected $fillable = [
        'design_id',
        'type',
        'path',
    ];

    protected $table = 'design_items';

    protected $appends = ['full_path'];

    protected $touches = ['designSetting'];

    public $timestamps = false;

    public function designSetting()
    {
        return $this->belongsTo(DesignSetting::class, 'design_id');
    }

    public function translations()
    {
        return $this->hasMany(DesignItemTranslation::class, 'item_id');
    }

    public function translation($lang = null)
    {
        $lang = $lang ?? app()->getLocale();
        return $this->translations()->where('lang', $lang)->first();
    }

    public function getFullPathAttribute()
    {
        if (!$this->path) {
            return null;
        }

        return url('/storage/' . $this->path);
    }
}

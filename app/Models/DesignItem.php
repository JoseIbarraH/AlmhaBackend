<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignItem extends Model
{
    protected $fillable = [
        'design_id',
        'lang',
        'type',
        'path',
        'title',
        'subtitle'
    ];

    protected $touches = ['designSetting'];

    public function designSetting()
    {
        return $this->belongsTo(DesignSetting::class, 'design_id');
    }

    public static function getOne($id, $lang = 'es', $default = null)
    {
        return static::where('design_id', $id)
            ->where('lang', $lang)
            ->first() ?? $default;
    }

    public static function getAll($id, $lang = 'es', $default = null)
    {
        $items = static::where('design_id', $id)
            ->where('lang', $lang)
            ->get();

        return $items->isNotEmpty() ? $items : $default;
    }


}

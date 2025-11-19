<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignItemTranslation extends Model
{
    protected $table = 'design_item_translations';

    protected $fillable = [
        'item_id',
        'lang',
        'title',
        'subtitle'
    ];

    protected $touches = ['DesignItem'];

    public $timestamps = false;

    public function designItem()
    {
        return $this->belongsTo(DesignItem::class, 'item_id');
    }
}

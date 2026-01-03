<?php

namespace App\Domains\Design\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class DesignItemTranslation extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;
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

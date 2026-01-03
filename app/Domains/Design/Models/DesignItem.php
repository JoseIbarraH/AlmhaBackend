<?php

namespace App\Domains\Design\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class DesignItem extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'design_id',
        'type',
        'path',
    ];

    protected $table = 'design_items';

    protected $touches = ['designSetting'];

    public $timestamps = false;

    public function designSetting()
    {
        return $this->belongsTo(DesignSetting::class, 'design_id');
    }

    public function translations($lang = null)
    {
        return $this->hasMany(DesignItemTranslation::class, 'item_id');
    }

    public function translation($lang = null)
    {
        $locale = $lang ?? app()->getLocale();
        return $this->hasOne(DesignItemTranslation::class, 'item_id')->where('lang', $locale);
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => match (true) {
                empty($value) => null,
                str_starts_with($value, 'http') => $value,
                default => asset("storage/{$value}"),
            },
        );
    }
}

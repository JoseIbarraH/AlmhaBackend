<?php

namespace App\Domains\Setting\Setting\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    private $fillable = [
        'key',
        'value',
        'group'
    ];

    protected $casts = [
        'value' => 'array',
    ];


    public function setValue(string $key, $value, ?string $group = null)
    {
        cache()->forget("setting.$key");
        return self::updateOrCreate(
            [
                'key' => $key
            ],
            [
                'value' => $value,
                'group' => $group
            ]
        );
    }

    public static function getValue(string $key, $default = null)
    {
        return cache()->rememberForever("setting.$key", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function findByGroup($group)
    {
        
    }
}

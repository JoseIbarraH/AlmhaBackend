<?php

namespace App\Domains\Setting\Setting\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'key',
        'value',
        'group'
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public $timestamps = false;

    public static function setValue(string $key, $value, ?string $group = null)
    {
        cache()->forget("setting.$key");

        $normalizedValue = match (true) {
            is_bool($value) => $value ? true : false,
            is_null($value) => '',
            is_array($value) || is_object($value) => json_encode($value),
            default => (string) $value,
        };

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $normalizedValue,
                'group' => $group,
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

    public static function findByGroup(string $group): array
    {
        return cache()->rememberForever("settings.group.$group", function () use ($group) {
            return self::where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

}

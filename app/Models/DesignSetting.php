<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignSetting extends Model
{
    protected $table = 'design_settings';

    protected $fillable = [
        'key',
        'value'
    ];

    public $timestamps = false;

    public function designItems()
    {
        return $this->hasMany(DesignItem::class, 'design_id');
    }

    /**
     * Obtener el valor de una configuración por clave.
     */
    public static function getOne(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function getAll(string $key, $default = null)
    {
        $record = static::where('key', $key)->first();
        return $record ?? $default;
    }

    /**
     * Establecer o actualizar el valor de una configuración.
     */
    public static function set(string $key, $value): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
        return $setting;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Obtiene el valor de una clave de configuración.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Establece el valor de una clave de configuración (crea o actualiza).
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Retorna todos los settings como array asociativo key => value.
     */
    public static function allAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}

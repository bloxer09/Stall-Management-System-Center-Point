<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        return \Illuminate\Support\Facades\Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('setting_key', $key)->first();
            return $setting ? $setting->setting_value : $default;
        });
    }

    public static function set(string $key, ?string $value): static
    {
        \Illuminate\Support\Facades\Cache::forget("setting_{$key}");
        return static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
    }
}

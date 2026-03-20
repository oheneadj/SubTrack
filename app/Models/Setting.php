<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get all settings as key => value array
     */
    public static function getAllAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}

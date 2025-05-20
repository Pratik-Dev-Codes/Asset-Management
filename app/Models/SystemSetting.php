<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'options',
        'is_public',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $group
     * @return SystemSetting
     */
    public static function setValue(string $key, $value, ?string $group = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group ?? self::getGroupFromKey($key),
            ]
        );

        // Clear cache
        \Cache::forget("system_setting_{$key}");
        \Cache::forget('system_settings');
        \Cache::forget("system_settings_{$setting->group}");

        return $setting;
    }

    /**
     * Get all settings as an associative array.
     *
     * @param bool $includePrivate
     * @return array
     */
    public static function getAll(bool $includePrivate = false): array
    {
        $query = $includePrivate 
            ? self::query()
            : self::where('is_public', true);

        return $query->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get settings by group.
     *
     * @param string $group
     * @param bool $includePrivate
     * @return array
     */
    public static function getByGroup(string $group, bool $includePrivate = false): array
    {
        $query = self::where('group', $group);
        
        if (!$includePrivate) {
            $query->where('is_public', true);
        }

        return $query->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get group from key.
     *
     * @param string $key
     * @return string
     */
    protected static function getGroupFromKey(string $key): string
    {
        $parts = explode('.', $key);
        return $parts[0] ?? 'general';
    }
}

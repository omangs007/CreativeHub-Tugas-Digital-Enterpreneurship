<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan aplikasi berbentuk key-value. Diubah lewat panel admin dan dicatat di audit log.
 */
class Setting extends Model
{
    public const COMMISSION_RATE_BPS = 'commission_rate_bps';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['*'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::whereKey($key)->value('value') ?? $default;
    }

    public static function put(string $key, string $value): void
    {
        $setting = static::find($key) ?? tap(new static, fn (self $s) => $s->key = $key);
        $setting->value = $value;
        $setting->save();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    protected $table = 'clinic_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function theme(): string
    {
        $theme = static::query()->whereKey('theme')->value('value');
        return in_array($theme, ['green', 'blue', 'violet'], true) ? $theme : 'green';
    }
}

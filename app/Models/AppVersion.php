<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'platform',
    'latest_version',
    'min_version',
    'force_update',
    'title',
    'release_notes',
    'apk_url',
    'store_url',
])]
class AppVersion extends Model
{
    protected function casts(): array
    {
        return [
            'force_update' => 'boolean',
        ];
    }

    public static function forPlatform(string $platform = 'android'): self
    {
        return static::firstOrCreate(
            ['platform' => strtolower($platform)],
            [
                'latest_version' => '1.0.0',
                'min_version' => '1.0.0',
                'force_update' => false,
                'title' => 'Update Available',
                'release_notes' => 'New features and improvements.',
                'apk_url' => 'https://sa.devtz.com/downloads/SmartAttend.apk',
                'store_url' => 'https://sa.devtz.com/downloads/SmartAttend.apk',
            ]
        );
    }
}

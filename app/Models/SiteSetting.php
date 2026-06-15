<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteSetting extends Model
{
    public const PUBLIC_FORM_BACKGROUND_IMAGE = 'public_form_background_image';

    public const DEFAULT_PUBLIC_FORM_BACKGROUND_IMAGE = 'images/cars/330i/lifestyle-showroom.png';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::query()
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public static function setValue(string $key, ?string $value, ?string $type = null): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
            ],
        );
    }

    public static function publicFormBackgroundImageUrl(): string
    {
        return static::imageUrl(static::getValue(static::PUBLIC_FORM_BACKGROUND_IMAGE))
            ?? asset(static::DEFAULT_PUBLIC_FORM_BACKGROUND_IMAGE);
    }

    public static function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim($path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');

        if (Str::startsWith($normalizedPath, 'storage/')) {
            return file_exists(public_path($normalizedPath)) ? asset($normalizedPath) : null;
        }

        if (file_exists(public_path($normalizedPath))) {
            return asset($normalizedPath);
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            return Storage::url($normalizedPath);
        }

        return null;
    }
}

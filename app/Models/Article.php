<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const CATEGORY_OFFERS = 'uu-dai-khach-hang';

    public const CATEGORY_SALES_PROGRAMS = 'chuong-trinh-ban-hang';

    public const CATEGORY_SHOWROOM_EVENTS = 'su-kien-showroom';

    public const CATEGORY_BMW_EXPERIENCE = 'trai-nghiem-bmw';

    public const CATEGORY_AFTERSALES = 'dich-vu-hau-mai';

    public const CATEGORY_SHOWROOM_NEWS = 'tin-tuc-showroom';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'category',
        'excerpt',
        'body',
        'cover_image',
        'status',
        'published_at',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_OFFERS => 'Ưu đãi cho khách hàng',
            self::CATEGORY_SALES_PROGRAMS => 'Chương trình bán hàng',
            self::CATEGORY_SHOWROOM_EVENTS => 'Sự kiện showroom',
            self::CATEGORY_BMW_EXPERIENCE => 'Trải nghiệm BMW',
            self::CATEGORY_AFTERSALES => 'Dịch vụ hậu mãi',
            self::CATEGORY_SHOWROOM_NEWS => 'Tin tức showroom',
        ];
    }

    /**
     * @return list<string>
     */
    public static function categoryValues(): array
    {
        return array_keys(self::categories());
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? $this->category;
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_PUBLISHED => 'Đã xuất bản',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return array_keys(self::statuses());
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function statusColorClass(): string
    {
        return $this->status === self::STATUS_PUBLISHED
            ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400'
            : 'border-zinc-700 bg-zinc-950 text-zinc-400';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function coverImageUrl(): ?string
    {
        if (blank($this->cover_image)) {
            return null;
        }

        $path = trim($this->cover_image);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');

        if (Str::startsWith($normalizedPath, 'storage/')) {
            return file_exists(public_path($normalizedPath))
                ? asset($normalizedPath)
                : null;
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            return Storage::url($normalizedPath);
        }

        if (file_exists(public_path($normalizedPath))) {
            return asset($normalizedPath);
        }

        return null;
    }

    public function editorialImageUrl(): string
    {
        return $this->coverImageUrl() ?? asset($this->fallbackCoverImagePath());
    }

    public function fallbackCoverImagePath(): string
    {
        return match ($this->category) {
            self::CATEGORY_OFFERS => 'images/cars/330i/lifestyle-showroom.png',
            self::CATEGORY_SALES_PROGRAMS => 'images/cars/530i.png',
            self::CATEGORY_SHOWROOM_EVENTS => 'images/cars/bmw-x3-m50-xdrive/lifestyle-showroom.png',
            self::CATEGORY_BMW_EXPERIENCE => 'images/cars/330i/cockpit-interior.png',
            self::CATEGORY_AFTERSALES => 'images/cars/bmw-550e-xdrive-sedan/design-detail.png',
            self::CATEGORY_SHOWROOM_NEWS => 'images/cars/hero.png',
            default => 'images/cars/hero.png',
        };
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = filled($baseSlug) ? $baseSlug : 'bai-viet';
        $slug = $baseSlug;
        $counter = 1;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}

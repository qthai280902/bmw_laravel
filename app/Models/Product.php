<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'type',
        'price',
        'deposit_amount',
        'stock',
        'specifications',
        'description',
        'is_featured',
        'is_active',
    ];

    /**
     * Thông số kỹ thuật dịch sang Tiếng Việt.
     */
    public const SPEC_TRANSLATIONS = [
        'Engine' => 'Động cơ',
        'Horsepower' => 'Công suất tối đa',
        'Torque' => 'Mô-men xoắn',
        '0-100km/h' => 'Tăng tốc 0-100km/h',
        '0-60mph' => 'Tăng tốc 0-60mph',
        'Top Speed' => 'Tốc độ tối đa',
        'Drivetrain' => 'Hệ dẫn động',
        'Transmission' => 'Hộp số',
        'Fuel Consumption' => 'Tiêu thụ nhiên liệu',
        'Range' => 'Quãng đường di chuyển',
        'Battery Capacity' => 'Dung lượng pin',
        'Seat Height' => 'Chiều cao yên',
        'Dry Weight' => 'Trọng lượng khô',
        'Fuel Tank' => 'Dung tích bình xăng',
        'Displacement' => 'Dung tích xy-lanh',
        'Luxury Features' => 'Tiện nghi cao cấp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'specifications' => 'array',
            'is_featured' => 'boolean',
            'price' => 'integer',
            'deposit_amount' => 'integer',
        ];
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the primary image for the product.
     * OPTIMIZED: Loaded as hasOne to save memory in lists.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate a unique slug based on the product name.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Get the translated specifications.
     *
     * @return array<string, string|array<int, string>>
     */
    public function getTranslatedSpecsAttribute(): array
    {
        $translated = [];
        $specs = $this->specifications ?? [];

        foreach ($specs as $key => $value) {
            $translatedKey = self::SPEC_TRANSLATIONS[$key] ?? $key;
            $translated[$translatedKey] = $value;
        }

        return $translated;
    }
}

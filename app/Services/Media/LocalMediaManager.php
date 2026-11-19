<?php

namespace App\Services\Media;

use App\Models\Article;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocalMediaManager
{
    /**
     * Only uploaded media inside these managed subdirectories may be deleted automatically.
     *
     * @var list<string>
     */
    public const MANAGED_DIRECTORIES = ['products', 'articles'];

    /**
     * Normalize a stored relative path ONLY when it safely resides inside managed upload directories.
     */
    public function normalizeManagedPath(?string $rawPath): ?string
    {
        if (blank($rawPath)) {
            return null;
        }

        $path = str_replace('\\', '/', trim((string) $rawPath));

        if (
            $path === ''
            || Str::startsWith($path, ['http://', 'https://', '//', 'data:'])
            || str_contains($path, '..')
        ) {
            return null;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        if (Str::startsWith($normalized, ['images/', 'assets/', 'build/'])) {
            return null;
        }

        foreach (self::MANAGED_DIRECTORIES as $dir) {
            if (Str::startsWith($normalized, $dir.'/')) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * Check whether any active DB record still references this managed relative path.
     */
    public function isReferencedInDatabase(
        string $managedPath,
        ?int $ignoreProductId = null,
        ?int $ignoreProductImageId = null,
        ?int $ignoreArticleId = null,
    ): bool {
        $candidates = [
            $managedPath,
            'storage/'.$managedPath,
            '/storage/'.$managedPath,
        ];

        $productImageReferenced = ProductImage::query()
            ->when($ignoreProductImageId !== null, fn ($query) => $query->whereKeyNot($ignoreProductImageId))
            ->when($ignoreProductId !== null, fn ($query) => $query->where('product_id', '!=', $ignoreProductId))
            ->whereHas('product', static function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->whereIn('path', $candidates)
            ->exists();

        if ($productImageReferenced) {
            return true;
        }

        $articleReferenced = Article::query()
            ->when($ignoreArticleId !== null, fn ($query) => $query->whereKeyNot($ignoreArticleId))
            ->where(static function ($query) use ($candidates, $managedPath): void {
                $query->whereIn('cover_image', $candidates)
                    ->orWhere('body', 'like', '%'.$managedPath.'%');
            })
            ->exists();

        if ($articleReferenced) {
            return true;
        }

        return SiteSetting::query()
            ->whereIn('value', $candidates)
            ->exists();
    }

    /**
     * Delete physical file from public disk ONLY if it resides in managed upload folders and has zero references.
     */
    public function deleteIfUnreferenced(
        ?string $rawPath,
        ?int $ignoreProductId = null,
        ?int $ignoreProductImageId = null,
        ?int $ignoreArticleId = null,
    ): bool {
        $managedPath = $this->normalizeManagedPath($rawPath);

        if ($managedPath === null) {
            return false;
        }

        if ($this->isReferencedInDatabase($managedPath, $ignoreProductId, $ignoreProductImageId, $ignoreArticleId)) {
            return false;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($managedPath)) {
            return false;
        }

        return $disk->delete($managedPath);
    }

    /**
     * Collect all currently referenced managed paths indexed by normalized path for orphan pruning.
     *
     * @return array<string, true>
     */
    public function collectReferencedManagedPaths(): array
    {
        $referenced = [];

        $activeProductIds = Product::query()->pluck('id')->all();

        if ($activeProductIds !== []) {
            ProductImage::query()
                ->whereIn('product_id', $activeProductIds)
                ->pluck('path')
                ->each(function (mixed $path) use (&$referenced): void {
                    $normalized = $this->normalizeManagedPath(is_string($path) ? $path : null);
                    if ($normalized !== null) {
                        $referenced[$normalized] = true;
                    }
                });
        }

        Article::query()
            ->select(['cover_image', 'body'])
            ->get()
            ->each(function (Article $article) use (&$referenced): void {
                $cover = $this->normalizeManagedPath($article->cover_image);
                if ($cover !== null) {
                    $referenced[$cover] = true;
                }

                if (filled($article->body) && preg_match_all('/(?:\/storage\/|products\/|articles\/)((?:products|articles)\/[^\s"\')<>]+|[^\s"\')<>]+)/i', (string) $article->body, $matches) > 0) {
                    foreach ($matches[0] as $match) {
                        $normalized = $this->normalizeManagedPath($match);
                        if ($normalized !== null) {
                            $referenced[$normalized] = true;
                        }
                    }
                }
            });

        SiteSetting::query()
            ->pluck('value')
            ->each(function (mixed $value) use (&$referenced): void {
                $normalized = $this->normalizeManagedPath(is_string($value) ? $value : null);
                if ($normalized !== null) {
                    $referenced[$normalized] = true;
                }
            });

        return $referenced;
    }
}

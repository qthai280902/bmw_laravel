<?php

namespace App\Ai\Tools;

use App\Models\Article;
use App\Services\Ai\ShowroomCatalogMatcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchArticlesAndOffersTool implements Tool
{
    public function __construct(private readonly ShowroomCatalogMatcher $matcher) {}

    public function name(): string
    {
        return 'search_articles_and_offers';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Tìm kiếm bài viết ưu đãi, sự kiện, dịch vụ hậu mãi và tin tức showroom BMW đã xuất bản công khai.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $keyword = trim((string) ($request['keyword'] ?? ''));
        $category = trim((string) ($request['category'] ?? ''));

        $query = Article::query()
            ->published()
            ->select(['id', 'title', 'slug', 'category', 'excerpt', 'body', 'published_at'])
            ->latest('published_at');

        if ($category !== '' && in_array($category, Article::categoryValues(), true)) {
            $query->where('category', $category);
        }

        $articles = $query->get();

        if ($keyword !== '') {
            $articles = $articles
                ->map(fn (Article $article): array => [
                    'article' => $article,
                    'score' => $this->matcher->scoreArticleQuery($article, $keyword),
                ])
                ->filter(fn (array $entry): bool => $entry['score'] > 0)
                ->sortByDesc('score')
                ->pluck('article')
                ->values();
        }

        $items = $articles
            ->take(5)
            ->map(fn (Article $article): array => [
                'title' => $article->title,
                'slug' => $article->slug,
                'category' => $article->categoryLabel(),
                'category_slug' => $article->category,
                'published_at' => optional($article->published_at)->format('d/m/Y'),
                'excerpt' => $this->matcher->compactText($article->excerpt ?: $article->body),
                'url' => route('articles.show', $article->slug, false),
            ])
            ->values()
            ->all();

        return json_encode([
            'status' => count($items) > 0 ? 'ok' : 'no_match',
            'total_found' => count($items),
            'articles' => $items,
            'offers_url' => route('offers.exclusive', [], false),
            'articles_index_url' => route('articles.index', [], false),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Từ khóa chủ đề bài viết hoặc ưu đãi cần tìm (ví dụ: ưu đãi, mùa hè, lái thử, bảo dưỡng, hậu mãi).')
                ->nullable(),
            'category' => $schema->string()
                ->enum(Article::categoryValues())
                ->description('Lọc theo chuyên mục bài viết hợp lệ của showroom.')
                ->nullable(),
        ];
    }
}

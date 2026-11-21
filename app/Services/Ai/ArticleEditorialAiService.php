<?php

namespace App\Services\Ai;

use App\Ai\Agents\ArticleEditorialAgent;
use App\Models\Article;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\AiManager;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ArticleEditorialAiService
{
    public function __construct(
        private GeminiAgentRunner $runner,
    ) {}

    /**
     * Generate editorial summary & SEO metadata with graceful fallback.
     *
     * @return array{excerpt: string, seo_title: string, seo_description: string, source: 'ai'|'fallback'}
     */
    public function generateMetadata(string $title, string $category, string $body): array
    {
        $cleanTitle = trim(strip_tags($title));
        $cleanBody = $this->normalizePlainBody($body);
        $categoryLabel = Article::categories()[$category] ?? 'Tin tức BMW Showroom';

        $isFaked = app(AiManager::class)->hasFakeGatewayFor(ArticleEditorialAgent::class);
        $aiEnabled = (bool) config('showroom_ai.article_editorial_enabled', true);

        if ($aiEnabled && $cleanBody !== '') {
            $prompt = $this->buildPrompt($cleanTitle, $categoryLabel, $cleanBody);

            try {
                if ($isFaked) {
                    $response = ArticleEditorialAgent::make()->prompt($prompt);

                    if ($response instanceof StructuredAgentResponse) {
                        return $this->normalizeOutput($response->toArray(), $cleanTitle, $cleanBody, 'ai');
                    }
                }

                $run = $this->runner->run(
                    fn (string $provider, ?string $model): mixed => ArticleEditorialAgent::make()->prompt(
                        prompt: $prompt,
                        provider: $provider,
                        model: $model,
                        timeout: max(8, (int) config('showroom_ai.timeout_seconds', 20)),
                    ),
                    operation: 'article_editorial_metadata',
                );

                if (($run['ok'] ?? false) === true && ($run['result'] ?? null) instanceof StructuredAgentResponse) {
                    return $this->normalizeOutput($run['result']->toArray(), $cleanTitle, $cleanBody, 'ai');
                }
            } catch (Throwable $exception) {
                Log::warning('Article editorial AI generation fell back to deterministic metadata.', [
                    'exception' => $exception::class,
                ]);
            }
        }

        return $this->deterministicFallback($cleanTitle, $categoryLabel, $cleanBody);
    }

    private function buildPrompt(string $title, string $categoryLabel, string $plainBody): string
    {
        $snippet = Str::limit($plainBody, 3000, '...');

        return <<<PROMPT
                Tiêu đề bài viết: {$title}
                Chuyên mục: {$categoryLabel}
                Nội dung bài viết:
                {$snippet}
                PROMPT;
    }

    /**
     * @param  array<string, mixed>  $structured
     * @return array{excerpt: string, seo_title: string, seo_description: string, source: 'ai'}
     */
    private function normalizeOutput(array $structured, string $title, string $plainBody, string $source): array
    {
        $fallback = $this->deterministicFallback($title, 'BMW Showroom', $plainBody);

        $excerpt = trim((string) ($structured['excerpt'] ?? ''));
        $seoTitle = trim((string) ($structured['seo_title'] ?? ''));
        $seoDescription = trim((string) ($structured['seo_description'] ?? ''));

        return [
            'excerpt' => Str::limit($excerpt !== '' ? $excerpt : $fallback['excerpt'], 480, '...'),
            'seo_title' => Str::limit($seoTitle !== '' ? $seoTitle : $fallback['seo_title'], 60, ''),
            'seo_description' => Str::limit($seoDescription !== '' ? $seoDescription : $fallback['seo_description'], 155, '...'),
            'source' => $source,
        ];
    }

    /**
     * @return array{excerpt: string, seo_title: string, seo_description: string, source: 'fallback'}
     */
    private function deterministicFallback(string $title, string $categoryLabel, string $plainBody): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $plainBody, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $summarySentences = array_slice($sentences, 0, 3);
        $excerptSource = $summarySentences !== [] ? implode(' ', $summarySentences) : $plainBody;

        if ($excerptSource === '') {
            $excerptSource = $title !== ''
                ? "{$title} — Cập nhật thông tin chính hãng và trải nghiệm đẳng cấp từ BMW Showroom Vietnam."
                : 'Khám phá tin tức, ưu đãi và trải nghiệm chính hãng tại BMW Showroom Vietnam.';
        }

        $seoTitleBase = $title !== '' ? $title : "{$categoryLabel} | BMW Showroom";

        return [
            'excerpt' => Str::limit(trim($excerptSource), 320, '...'),
            'seo_title' => Str::limit(trim($seoTitleBase), 60, ''),
            'seo_description' => Str::limit(trim($excerptSource), 155, '...'),
            'source' => 'fallback',
        ];
    }

    private function normalizePlainBody(string $markdownOrHtml): string
    {
        $stripped = preg_replace([
            '/!\[[^\]]*\]\([^)]*\)/u',
            '/\[([^\]]+)\]\([^)]*\)/u',
            '/^#{1,6}\s*/mu',
            '/^>\s*/mu',
        ], [
            '',
            '$1',
            '',
            '',
        ], $markdownOrHtml) ?? $markdownOrHtml;

        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($stripped)));
    }
}

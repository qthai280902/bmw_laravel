<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSeoContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_seeder_is_idempotent_and_covers_public_categories(): void
    {
        User::factory()->create(['email' => 'admin@bmw.com']);

        $this->seed(ArticleSeeder::class);

        $firstCount = Article::query()->count();
        $firstSlugs = Article::query()->pluck('slug')->sort()->values()->all();

        $this->seed(ArticleSeeder::class);

        $this->assertSame($firstCount, Article::query()->count());
        $this->assertSame($firstSlugs, Article::query()->pluck('slug')->sort()->values()->all());
        $this->assertSame($firstCount, Article::query()->distinct('slug')->count('slug'));

        foreach (Article::categoryValues() as $category) {
            $this->assertGreaterThanOrEqual(
                2,
                Article::published()->where('category', $category)->count(),
                "Category {$category} should have at least two published articles.",
            );
        }
    }

    public function test_public_article_cards_use_editorial_image_fallbacks(): void
    {
        $article = Article::factory()->published()->create([
            'category' => Article::CATEGORY_SHOWROOM_NEWS,
            'cover_image' => null,
        ]);

        $this->assertStringContainsString('/images/cars/hero.png', $article->editorialImageUrl());

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('/images/cars/hero.png', false);
    }
}

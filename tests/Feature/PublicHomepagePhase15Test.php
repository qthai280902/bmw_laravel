<?php

namespace Tests\Feature;

use App\Models\Article;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomepagePhase15Test extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_quality_articles_without_browser_qa_content(): void
    {
        Article::factory()->published()->create([
            'title' => 'Browser QA Published 1781444047469',
            'slug' => 'browser-qa-published-1781444047469',
        ]);

        $this->seed(ArticleSeeder::class);

        $this->assertDatabaseHas(Article::class, [
            'slug' => 'browser-qa-published-1781444047469',
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tìm hiểu thêm')
            ->assertSee('Ưu đãi mùa hè BMW 2026 cho khách hàng đặt lịch showroom')
            ->assertSee('Gói hỗ trợ tài chính khi sở hữu BMW mới')
            ->assertSee('Chương trình lái thử và tư vấn cấu hình BMW tại showroom')
            ->assertDontSee('Browser QA Published')
            ->assertDontSee('href="#"', false);
    }
}

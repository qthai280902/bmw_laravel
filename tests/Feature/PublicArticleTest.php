<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_displays_learn_more_section_with_latest_published_articles(): void
    {
        $publishedArticle = Article::factory()->published()->create([
            'title' => 'Trải nghiệm BMW cuối tuần',
            'slug' => 'trai-nghiem-bmw-cuoi-tuan',
        ]);
        $draftArticle = Article::factory()->create([
            'title' => 'Bản nháp ưu đãi nội bộ',
            'slug' => 'ban-nhap-uu-dai-noi-bo',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tìm hiểu thêm')
            ->assertSee($publishedArticle->title)
            ->assertDontSee($draftArticle->title);
    }

    public function test_public_article_index_lists_only_published_articles(): void
    {
        $publishedArticle = Article::factory()->published()->create([
            'title' => 'Ưu đãi chăm sóc BMW',
            'slug' => 'uu-dai-cham-soc-bmw',
        ]);
        $draftArticle = Article::factory()->create([
            'title' => 'Bản nháp sự kiện showroom',
            'slug' => 'ban-nhap-su-kien-showroom',
        ]);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee($publishedArticle->title)
            ->assertDontSee($draftArticle->title);
    }

    public function test_public_article_detail_only_allows_published_articles(): void
    {
        $publishedArticle = Article::factory()->published()->create([
            'title' => 'Sự kiện lái thử BMW',
            'slug' => 'su-kien-lai-thu-bmw',
        ]);
        $draftArticle = Article::factory()->create([
            'title' => 'Bài nháp chưa xuất bản',
            'slug' => 'bai-nhap-chua-xuat-ban',
        ]);

        $this->get(route('articles.show', $publishedArticle))
            ->assertOk()
            ->assertSee($publishedArticle->title);

        $this->get(route('articles.show', $draftArticle))
            ->assertNotFound();
    }

    public function test_public_article_category_filter_limits_results(): void
    {
        $offerArticle = Article::factory()->published()->create([
            'title' => 'Ưu đãi cho khách hàng BMW',
            'category' => Article::CATEGORY_OFFERS,
        ]);
        $eventArticle = Article::factory()->published()->create([
            'title' => 'Sự kiện showroom BMW',
            'category' => Article::CATEGORY_SHOWROOM_EVENTS,
        ]);

        $this->get(route('articles.index', ['category' => Article::CATEGORY_OFFERS]))
            ->assertOk()
            ->assertSee($offerArticle->title)
            ->assertDontSee($eventArticle->title);
    }
}

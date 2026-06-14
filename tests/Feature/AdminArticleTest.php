<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_article_index_and_create_form(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $article = Article::factory()->published()->create([
            'title' => 'Ưu đãi BMW tháng 6',
            'category' => Article::CATEGORY_OFFERS,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee('Bài viết')
            ->assertSee($article->title);

        $this->actingAs($admin)
            ->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee('Tạo bài viết');
    }

    public function test_admin_can_create_published_article_with_cover_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'admin@bmw.com']);

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'title' => 'Ưu đãi BMW tháng 6',
                'category' => Article::CATEGORY_OFFERS,
                'excerpt' => 'Chương trình ưu đãi dành cho khách hàng showroom.',
                'body' => 'Nội dung chương trình ưu đãi BMW tháng 6.',
                'cover_image' => $this->fakePngUpload('cover.png'),
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => null,
            ])
            ->assertRedirect();

        $article = Article::query()->where('slug', 'uu-dai-bmw-thang-6')->firstOrFail();

        $this->assertSame($admin->id, $article->user_id);
        $this->assertSame(Article::STATUS_PUBLISHED, $article->status);
        $this->assertNotNull($article->published_at);
        $this->assertNotNull($article->cover_image);
        Storage::disk('public')->assertExists($article->cover_image);
    }

    public function test_admin_can_create_draft_article_without_public_timestamp(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'title' => 'Bản nháp sự kiện showroom',
                'category' => Article::CATEGORY_SHOWROOM_EVENTS,
                'excerpt' => 'Nội dung đang chuẩn bị.',
                'body' => 'Bài viết này chưa sẵn sàng để xuất bản.',
                'status' => Article::STATUS_DRAFT,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(Article::class, [
            'slug' => 'ban-nhap-su-kien-showroom',
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function test_admin_can_update_and_delete_article(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $article = Article::factory()->create([
            'title' => 'Tin showroom cũ',
            'slug' => 'tin-showroom-cu',
            'cover_image' => 'articles/old-cover.jpg',
        ]);
        Storage::disk('public')->put('articles/old-cover.jpg', 'old image');

        $this->actingAs($admin)
            ->patch(route('admin.articles.update', $article), [
                'title' => 'Tin showroom mới',
                'slug' => 'tin-showroom-moi',
                'category' => Article::CATEGORY_SHOWROOM_NEWS,
                'excerpt' => 'Cập nhật showroom.',
                'body' => 'Nội dung cập nhật showroom BMW.',
                'cover_image' => $this->fakePngUpload('new-cover.png'),
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => '2026-06-14 10:00:00',
            ])
            ->assertRedirect(route('admin.articles.edit', 'tin-showroom-moi'));

        $article->refresh();

        $this->assertSame('Tin showroom mới', $article->title);
        $this->assertSame('tin-showroom-moi', $article->slug);
        Storage::disk('public')->assertMissing('articles/old-cover.jpg');
        Storage::disk('public')->assertExists($article->cover_image);

        $coverImage = $article->cover_image;

        $this->actingAs($admin)
            ->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseMissing(Article::class, ['id' => $article->id]);
        Storage::disk('public')->assertMissing($coverImage);
    }

    public function test_article_validation_and_admin_middleware_are_enforced(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'title' => '',
                'category' => 'invalid-category',
                'body' => '',
                'status' => 'invalid-status',
            ])
            ->assertSessionHasErrors(['title', 'category', 'body', 'status']);

        $this->actingAs($user)
            ->get(route('admin.articles.index'))
            ->assertForbidden();
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'article-cover-');

        file_put_contents(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true),
        );

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}

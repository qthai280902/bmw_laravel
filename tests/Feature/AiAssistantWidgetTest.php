<?php

namespace Tests\Feature;

use App\Enums\VehicleType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiAssistantWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_renders_ready_draggable_ai_widget(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-ai-assistant-widget', false)
            ->assertSee('data-ai-draggable="true"', false)
            ->assertSee('Trợ lý ảo AI sẵn sàng')
            ->assertSee('Trợ lý ảo AI của BMW Showroom xin chào')
            ->assertSee('Tư vấn xe theo ngân sách')
            ->assertSee('So sánh BMW 330i và 530i')
            ->assertSee('ai-assistant-idle', false)
            ->assertSee('data-ai-drag-handle', false);
    }

    public function test_public_core_pages_render_widget_and_continue_to_load(): void
    {
        $vehicle = $this->createVehicle();
        $accessory = $this->createAccessory();
        $article = Article::factory()->published()->create([
            'title' => 'Ưu đãi public BMW',
        ]);

        foreach ([
            route('home'),
            route('products.index'),
            route('products.show', $vehicle->slug),
            route('accessories.index'),
            route('articles.index'),
            route('articles.show', $article->slug),
            route('appointments.create', ['type' => 'consult']),
            route('accessory-orders.create', $accessory->slug),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-ai-assistant-widget', false);
        }
    }

    public function test_admin_pages_do_not_render_public_ai_widget(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@bmw.com',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-ai-assistant-widget', false);
    }

    public function test_widget_assets_include_drag_storage_and_reduced_motion_hooks(): void
    {
        $script = File::get(resource_path('js/app.js'));
        $styles = File::get(resource_path('css/app.css'));
        $widget = File::get(resource_path('views/components/public/ai-assistant-widget.blade.php'));

        $this->assertStringContainsString('window.aiAssistantWidget', $script);
        $this->assertStringContainsString('localStorage.setItem', $script);
        $this->assertStringContainsString('clampedPosition', $script);
        $this->assertStringContainsString('prefers-reduced-motion', $styles);
        $this->assertStringContainsString('ai-assistant-ready-pulse', $styles);
        $this->assertStringContainsString('data-ai-draggable', $widget);
    }

    public function test_views_do_not_use_empty_hash_links(): void
    {
        $viewFiles = File::allFiles(resource_path('views'));

        foreach ($viewFiles as $file) {
            $this->assertStringNotContainsString(
                'href="#"',
                File::get($file->getPathname()),
                $file->getRelativePathname().' contains an empty hash link.'
            );
        }
    }

    private function createVehicle(): Product
    {
        return Product::factory()
            ->for(Category::factory()->create([
                'name' => 'Sedan',
                'slug' => 'sedan-'.Str::random(6),
            ]))
            ->create([
                'name' => 'BMW 330i Sedan',
                'slug' => 'bmw-330i-sedan',
                'type' => VehicleType::CAR,
                'is_active' => true,
            ]);
    }

    private function createAccessory(): Product
    {
        return Product::factory()
            ->for(Category::factory()->create([
                'name' => 'Phụ kiện ô tô',
                'slug' => 'phu-kien-o-to-'.Str::random(6),
            ]))
            ->create([
                'name' => 'Thảm lót sàn M Performance',
                'slug' => 'tham-lot-san-m-performance',
                'type' => VehicleType::ACCESSORY,
                'is_active' => true,
            ]);
    }
}

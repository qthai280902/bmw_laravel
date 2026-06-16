<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\VehicleType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Services\Ai\ShowroomAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class Phase16_2AiAssistantContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.model' => null,
            'showroom_ai.max_context_products' => 8,
            'ai.providers.gemini.key' => 'testing-gemini-key',
        ]);
    }

    public function test_public_context_includes_cars_motorbikes_accessories_and_articles_even_when_config_limit_is_low(): void
    {
        $car = $this->createProduct('BMW 330i Sedan', 'bmw-330i-sedan', VehicleType::CAR, 'Sedan');
        $motorbike = $this->createProduct('BMW S1000RR', 'bmw-s1000rr', VehicleType::MOTORBIKE, 'Motorrad');
        $accessory = $this->createProduct('Ốp Carbon bảo vệ động cơ S1000RR', 'op-carbon-bao-ve-dong-co-s1000rr', VehicleType::ACCESSORY, 'Phụ kiện Motorrad');
        $article = Article::factory()->published()->create([
            'title' => 'Ưu đãi BMW Motorrad',
            'excerpt' => 'Thông tin public cho khách hàng Motorrad.',
        ]);

        $context = app(ShowroomAssistantService::class)->publicContext('tìm giúp tôi chiếc bmw s1000rr');
        $names = collect($context['products'])->pluck('name');

        $this->assertTrue($names->contains($car->name));
        $this->assertTrue($names->contains($motorbike->name));
        $this->assertTrue($names->contains($accessory->name));

        $s1000rr = collect($context['products'])->firstWhere('name', 'BMW S1000RR');

        $this->assertSame('BMW Motorrad', $s1000rr['product_line']);
        $this->assertContains('bmws1000rr', $s1000rr['search_aliases']);
        $this->assertContains('s1000rr', $s1000rr['search_aliases']);
        $this->assertSame(route('products.show', 'bmw-s1000rr', false), $s1000rr['url']);
        $this->assertContains($article->title, collect($context['articles'])->pluck('title'));
    }

    public function test_s1000rr_alias_variants_return_the_motorbike_suggestion_without_real_ai_calls(): void
    {
        $motorbike = $this->createProduct('BMW S1000RR', 'bmw-s1000rr', VehicleType::MOTORBIKE, 'Motorrad');

        foreach ([
            'bmw s1000rr',
            's1000rr',
            's 1000 rr',
            'bmw s 1000 rr',
        ] as $message) {
            ShowroomAssistant::fake(['BMW S1000RR là mẫu BMW Motorrad có trong dữ liệu public.'])->preventStrayPrompts();

            $this->postJson(route('ai.showroom-assistant.ask'), [
                'message' => $message,
            ])
                ->assertOk()
                ->assertJsonPath('status', 'ok')
                ->assertJsonFragment([
                    'label' => 'Xem '.$motorbike->name,
                    'url' => route('products.show', $motorbike->slug, false),
                ]);

            ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt) use ($motorbike): bool {
                return str_contains($prompt->prompt, $motorbike->name)
                    && str_contains($prompt->prompt, 'BMW Motorrad')
                    && str_contains($prompt->prompt, 'BMW showroom gồm ô tô BMW, BMW Motorrad, phụ kiện');
            });
        }
    }

    public function test_motorbike_prompt_does_not_describe_the_showroom_as_car_only(): void
    {
        $this->createProduct('BMW S1000RR', 'bmw-s1000rr', VehicleType::MOTORBIKE, 'Motorrad');

        ShowroomAssistant::fake(['BMW Motorrad có trong showroom.'])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'tư vấn BMW Motorrad cho tôi',
        ])->assertOk();

        ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt): bool {
            return str_contains($prompt->prompt, 'BMW Motorrad')
                && ! str_contains($prompt->prompt, 'chỉ có ô tô');
        });
    }

    private function createProduct(string $name, string $slug, VehicleType $type, string $categoryName): Product
    {
        $category = Category::factory()->create([
            'name' => $categoryName,
            'slug' => Str::slug($categoryName).'-'.Str::random(6),
        ]);

        return Product::factory()
            ->for($category)
            ->create([
                'name' => $name,
                'slug' => $slug,
                'type' => $type,
                'is_active' => true,
                'price' => 500000000,
                'description' => 'Public showroom product.',
                'specifications' => [
                    'Horsepower' => '205 hp',
                ],
            ]);
    }
}

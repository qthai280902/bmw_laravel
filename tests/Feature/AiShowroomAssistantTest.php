<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class AiShowroomAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.model' => null,
            'ai.providers.gemini.key' => 'testing-gemini-key',
        ]);
    }

    public function test_guest_can_ask_showroom_assistant_with_fake_ai(): void
    {
        $vehicle = $this->createVehicle([
            'name' => 'BMW 330i Sedan',
            'slug' => 'bmw-330i-sedan',
        ]);
        $this->createVehicle([
            'name' => 'BMW 530i Sedan',
            'slug' => 'bmw-530i-sedan',
        ]);
        Article::factory()->published()->create([
            'title' => 'Ưu đãi BMW mùa hè',
            'excerpt' => 'Thông tin ưu đãi public của showroom.',
        ]);

        ShowroomAssistant::fake([
            'BMW 330i phù hợp nếu bạn muốn sedan thể thao. Có thể xem chi tiết hoặc đặt lịch tư vấn.',
        ])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'BMW 330i khác gì BMW 530i?',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('answer', 'BMW 330i phù hợp nếu bạn muốn sedan thể thao. Có thể xem chi tiết hoặc đặt lịch tư vấn.')
            ->assertJsonFragment([
                'label' => 'Xem '.$vehicle->name,
                'url' => route('products.show', $vehicle->slug, false),
            ]);

        ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt): bool {
            return Str::contains($prompt->prompt, 'BMW 330i Sedan')
                && Str::contains($prompt->prompt, 'Ưu đãi BMW mùa hè')
                && Str::contains($prompt->prompt, 'Dữ liệu public của showroom BMW');
        });
    }

    public function test_assistant_prompt_excludes_private_customer_data_and_draft_articles(): void
    {
        $vehicle = $this->createVehicle([
            'name' => 'BMW X5 Public',
            'slug' => 'bmw-x5-public',
        ]);
        Article::factory()->published()->create([
            'title' => 'Tin public showroom',
        ]);
        Article::factory()->create([
            'title' => 'Draft internal campaign',
        ]);
        Appointment::factory()->create([
            'product_id' => $vehicle->id,
            'guest_email' => 'secret-appointment@example.com',
            'guest_phone' => '0909999999',
            'notes' => 'private appointment note',
            'type' => AppointmentType::Consult,
            'status' => AppointmentStatus::Pending,
        ]);
        AccessoryOrder::create([
            'product_id' => $this->createAccessory()->id,
            'customer_name' => 'Private Customer',
            'customer_phone' => '0911111111',
            'customer_address' => 'Private Address',
            'customer_email' => 'secret-order@example.com',
            'quantity' => 1,
            'notes' => 'private order note',
            'admin_notes' => 'private admin note',
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        ShowroomAssistant::fake(['Public answer only.'])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tư vấn giúp tôi một xe gầm cao',
        ])->assertOk();

        ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt): bool {
            return Str::contains($prompt->prompt, 'BMW X5 Public')
                && Str::contains($prompt->prompt, 'Tin public showroom')
                && ! Str::contains($prompt->prompt, [
                    'secret-appointment@example.com',
                    'secret-order@example.com',
                    'private appointment note',
                    'private order note',
                    'private admin note',
                    'Draft internal campaign',
                ]);
        });
    }

    public function test_accessory_question_suggests_order_link_without_test_drive(): void
    {
        $accessory = $this->createAccessory([
            'name' => 'Thảm lót sàn M Performance',
            'slug' => 'tham-lot-san-m-performance',
        ]);

        ShowroomAssistant::fake(['Bạn có thể đặt phụ kiện qua form đặt hàng.'])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tôi muốn mua phụ kiện thảm lót sàn',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'label' => 'Đặt phụ kiện',
                'url' => route('accessory-orders.create', $accessory->slug, false),
            ])
            ->assertJsonMissing([
                'type' => 'test_drive',
            ]);
    }

    public function test_message_validation_rejects_empty_and_too_long_messages(): void
    {
        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => '',
        ])->assertUnprocessable();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => str_repeat('a', 601),
        ])->assertUnprocessable();
    }

    public function test_ai_endpoint_is_rate_limited(): void
    {
        config([
            'showroom_ai.gemini_keys.primary' => null,
            'showroom_ai.gemini_keys.additional' => null,
            'ai.providers.gemini.key' => null,
        ]);

        for ($index = 0; $index < 12; $index++) {
            $this
                ->withServerVariables(['REMOTE_ADDR' => '203.0.113.16'])
                ->postJson(route('ai.showroom-assistant.ask'), [
                    'message' => 'Tư vấn xe BMW',
                ])
                ->assertOk();
        }

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.16'])
            ->postJson(route('ai.showroom-assistant.ask'), [
                'message' => 'Tư vấn xe BMW',
            ])
            ->assertTooManyRequests();
    }

    private function createVehicle(array $attributes = []): Product
    {
        $category = Category::factory()->create([
            'name' => 'Sedan',
            'slug' => 'sedan-'.Str::random(6),
        ]);

        return Product::factory()
            ->for($category)
            ->create(array_merge([
                'type' => VehicleType::CAR,
                'is_active' => true,
                'price' => 3000000000,
            ], $attributes));
    }

    private function createAccessory(array $attributes = []): Product
    {
        $category = Category::factory()->create([
            'name' => 'Phụ kiện ô tô',
            'slug' => 'phu-kien-o-to-'.Str::random(6),
        ]);

        return Product::factory()
            ->for($category)
            ->create(array_merge([
                'type' => VehicleType::ACCESSORY,
                'is_active' => true,
                'price' => 12000000,
            ], $attributes));
    }
}

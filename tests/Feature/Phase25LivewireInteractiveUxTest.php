<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\AccessoryOrderCalculator;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\ArticleHub;
use App\Livewire\BookingFormWizard;
use App\Livewire\CatalogBrowser;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase25LivewireInteractiveUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_browser_filters_searches_sorts_paginates_and_resets(): void
    {
        $sedan = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $sav = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);
        $accessoryCat = Category::factory()->create(['name' => 'Phụ kiện M Performance', 'slug' => 'phu-kien-m-performance']);

        $car330i = Product::factory()->for($sedan)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_active' => true,
        ]);

        $carX3 = Product::factory()->for($sav)->create([
            'name' => 'BMW X3 M50 xDrive',
            'slug' => 'bmw-x3-m50-xdrive',
            'type' => VehicleType::CAR,
            'price' => 3999000000,
            'is_active' => true,
        ]);

        $motoS1000 = Product::factory()->for($sedan)->create([
            'name' => 'BMW S1000RR',
            'slug' => 'bmw-s1000rr',
            'type' => VehicleType::MOTORBIKE,
            'price' => 949000000,
            'is_active' => true,
        ]);

        $hiddenCar = Product::factory()->for($sedan)->create([
            'name' => 'BMW Hidden Concept',
            'slug' => 'bmw-hidden-concept',
            'type' => VehicleType::CAR,
            'price' => 9000000000,
            'is_active' => false,
        ]);

        $accessory = Product::factory()->for($accessoryCat)->create([
            'name' => 'M Performance Steering Wheel',
            'slug' => 'm-performance-steering-wheel',
            'type' => VehicleType::ACCESSORY,
            'price' => 25000000,
            'is_active' => true,
        ]);

        Livewire::test(CatalogBrowser::class)
            ->assertSee($car330i->name)
            ->assertSee($carX3->name)
            ->assertDontSee($hiddenCar->name)
            ->set('search', 'X3 M50')
            ->assertSee($carX3->name)
            ->assertDontSee($car330i->name)
            ->call('resetFilters')
            ->assertSee($car330i->name)
            ->set('type', VehicleType::MOTORBIKE->value)
            ->assertSee($motoS1000->name)
            ->assertDontSee($car330i->name)
            ->call('selectType', '')
            ->set('minPrice', '3000000000')
            ->set('maxPrice', '4500000000')
            ->assertSee($carX3->name)
            ->assertDontSee($car330i->name)
            ->call('resetFilters')
            ->set('sort', 'price_high')
            ->assertSeeInOrder([$carX3->name, $car330i->name, $motoS1000->name]);

        Livewire::test(CatalogBrowser::class, ['lockedType' => 'accessory'])
            ->call('selectType', VehicleType::CAR->value)
            ->set('type', VehicleType::CAR->value)
            ->assertSet('type', 'accessory')
            ->assertSee($accessory->name)
            ->assertDontSee($car330i->name);

        // Verify soft-rounded BMW Livewire pagination renders cleanly without rounded-none or rounded-md
        Product::factory()->count(11)->for($sedan)->create([
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        Livewire::test(CatalogBrowser::class)
            ->assertSee('wire:click="nextPage(\'page\')"', false)
            ->assertSee('rounded-xl', false)
            ->assertDontSee('rounded-none', false)
            ->assertDontSee('rounded-md', false)
            ->call('nextPage')
            ->assertSet('paginators.page', 2);
    }

    public function test_article_hub_filters_by_category_searches_and_paginates_published_articles(): void
    {
        $offerArticle = Article::factory()->published()->create([
            'title' => 'Ưu đãi BMW tháng 11 đặc quyền',
            'slug' => 'uu-dai-bmw-thang-11',
            'category' => Article::CATEGORY_OFFERS,
        ]);

        $eventArticle = Article::factory()->published()->create([
            'title' => 'Triển lãm BMW M Track Day',
            'slug' => 'trien-lam-bmw-m-track-day',
            'category' => Article::CATEGORY_SHOWROOM_EVENTS,
        ]);

        $draftArticle = Article::factory()->create([
            'title' => 'Bài nháp nội bộ chưa phát hành',
            'slug' => 'bai-nhap-noi-bo',
            'status' => Article::STATUS_DRAFT,
        ]);

        Livewire::test(ArticleHub::class)
            ->assertSee($offerArticle->title)
            ->assertSee($eventArticle->title)
            ->assertDontSee($draftArticle->title)
            ->call('selectCategory', Article::CATEGORY_OFFERS)
            ->assertSee($offerArticle->title)
            ->assertDontSee($eventArticle->title)
            ->call('selectCategory', '')
            ->set('search', 'Track Day')
            ->assertSee($eventArticle->title)
            ->assertDontSee($offerArticle->title)
            ->call('resetFilters')
            ->assertSee($offerArticle->title)
            ->assertSee($eventArticle->title);

        Article::factory()->count(9)->published()->create([
            'category' => Article::CATEGORY_SHOWROOM_NEWS,
        ]);

        Livewire::test(ArticleHub::class)
            ->assertSee('wire:click="nextPage(\'page\')"', false)
            ->assertSee('rounded-xl', false)
            ->assertDontSee('rounded-none', false)
            ->assertDontSee('rounded-md', false)
            ->call('nextPage')
            ->assertSet('paginators.page', 2);
    }

    public function test_booking_form_wizard_cascades_categories_switches_modes_and_validates_realtime(): void
    {
        $sedan = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $moto = Category::factory()->create(['name' => 'Motorrad', 'slug' => 'motorrad']);

        $car = Product::factory()->for($sedan)->create([
            'name' => 'BMW 530i Luxury',
            'slug' => 'bmw-530i-luxury',
            'type' => VehicleType::CAR,
            'price' => 3289000000,
            'is_active' => true,
        ]);

        $motorbike = Product::factory()->for($moto)->create([
            'name' => 'BMW R 1300 GS',
            'slug' => 'bmw-r-1300-gs',
            'type' => VehicleType::MOTORBIKE,
            'price' => 1099000000,
            'is_active' => true,
        ]);

        $hiddenCar = Product::factory()->for($sedan)->create([
            'name' => 'BMW Hidden Secret Car',
            'slug' => 'bmw-hidden-secret-car',
            'type' => VehicleType::CAR,
            'is_active' => false,
        ]);

        Livewire::test(BookingFormWizard::class)
            ->set('guestName', 'Nguyen Van Interactive')
            ->set('categoryType', 'oto')
            ->assertSee($car->name)
            ->assertDontSee($motorbike->name)
            ->assertDontSee($hiddenCar->name)
            ->set('productId', (string) $car->id)
            ->assertSee('3,289,000,000')
            ->set('categoryType', 'xe_may')
            ->assertSet('productId', '')
            ->assertSee($motorbike->name)
            ->set('type', AppointmentType::Maintenance->value)
            ->assertSet('isService', true)
            ->assertSet('guestName', 'Nguyen Van Interactive')
            ->assertSee('name="customer_car_model"', false)
            ->set('guestPhone', '123')
            ->assertHasErrors(['guestPhone']);

        $this->get(route('appointments.create', [
            'type' => 'test_drive',
            'product_id' => $car->id,
        ]))
            ->assertOk()
            ->assertSee($car->name)
            ->assertSee('name="type"', false)
            ->assertSee('name="appointment_date"', false);
    }

    public function test_accessory_order_calculator_updates_subtotal_and_validates_realtime(): void
    {
        $accessoryCat = Category::factory()->create(['name' => 'Phụ kiện Chính hãng', 'slug' => 'phu-kien-chinh-hang']);
        $accessory = Product::factory()->for($accessoryCat)->create([
            'name' => 'M Carbon Key Cover',
            'slug' => 'm-carbon-key-cover',
            'type' => VehicleType::ACCESSORY,
            'price' => 2500000,
            'is_active' => true,
        ]);

        Livewire::test(AccessoryOrderCalculator::class, ['product' => $accessory])
            ->assertSet('quantity', 1)
            ->assertSet('totalAmount', 2500000)
            ->assertSee('2,500,000')
            ->call('incrementQuantity')
            ->call('incrementQuantity')
            ->assertSet('quantity', 3)
            ->assertSet('totalAmount', 7500000)
            ->assertSee('7,500,000')
            ->call('decrementQuantity')
            ->assertSet('quantity', 2)
            ->assertSet('totalAmount', 5000000)
            ->set('quantity', 0)
            ->assertSet('quantity', 1)
            ->assertSet('totalAmount', 2500000)
            ->set('customerPhone', 'abc')
            ->assertHasErrors(['customerPhone']);
    }

    public function test_admin_crm_livewire_tables_enforce_admin_authorization_and_inline_updates(): void
    {
        $customer = User::factory()->create(['email' => 'customer@example.com']);
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);

        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $accessoryCat = Category::factory()->create(['name' => 'Phụ kiện', 'slug' => 'phu-kien']);

        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 320i Sport Line',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->for($accessoryCat)->create([
            'name' => 'Thảm cao su BMW',
            'type' => VehicleType::ACCESSORY,
            'price' => 4500000,
            'is_active' => true,
        ]);

        $appointment = Appointment::factory()->guest()->create([
            'product_id' => $car->id,
            'guest_name' => 'Tran Quoc CRM',
            'guest_phone' => '0909111222',
            'type' => AppointmentType::TestDrive,
            'status' => AppointmentStatus::Pending,
            'appointment_date' => now()->addDays(2),
        ]);

        $order = AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Le Thi Parts',
            'customer_phone' => '0909333444',
            'customer_address' => '123 Nguyen Hue',
            'quantity' => 2,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        // Non-admin customers must be blocked with 403
        Livewire::actingAs($customer)
            ->test(AppointmentTable::class)
            ->assertForbidden();

        Livewire::actingAs($customer)
            ->test(AccessoryOrderTable::class)
            ->assertForbidden();

        // Admin inline Appointment status update & search filter
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->assertSee('Tran Quoc CRM')
            ->set('search', 'Tran Quoc')
            ->assertSee($car->name)
            ->call('updateStatus', $appointment->id, AppointmentStatus::Confirmed->value)
            ->assertSee("Đã cập nhật trạng thái lịch hẹn #{$appointment->id} thành công.");

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);

        // Admin inline AccessoryOrder status update & confirmed_at timestamp
        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->assertSee('Le Thi Parts')
            ->assertSee('9,000,000 VNĐ')
            ->set('search', '#'.$order->id)
            ->assertSee($accessory->name)
            ->call('updateStatus', $order->id, AccessoryOrder::STATUS_CONFIRMED)
            ->assertSee("Đơn phụ kiện #{$order->id} đã được cập nhật trạng thái.");

        $freshOrder = $order->fresh();
        $this->assertSame(AccessoryOrder::STATUS_CONFIRMED, $freshOrder->status);
        $this->assertNotNull($freshOrder->confirmed_at);
    }

    public function test_single_alpine_and_livewire_script_config_render_cleanly_across_routes(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $accessoryCat = Category::factory()->create(['name' => 'Phụ kiện', 'slug' => 'phu-kien-layout']);
        $accessory = Product::factory()->for($accessoryCat)->create([
            'name' => 'M Performance Roof Box',
            'slug' => 'm-performance-roof-box',
            'type' => VehicleType::ACCESSORY,
            'price' => 18500000,
            'is_active' => true,
        ]);

        $publicRoutes = [
            route('products.index'),
            route('accessories.index'),
            route('articles.index'),
            route('appointments.create'),
            route('accessory-orders.create', $accessory->slug),
        ];

        foreach ($publicRoutes as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('livewireScriptConfig', false)
                ->assertSee('aiAssistantWidget', false);
        }

        $adminRoutes = [
            route('admin.appointments.index'),
            route('admin.accessory-orders.index'),
        ];

        foreach ($adminRoutes as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('livewireScriptConfig', false);
        }
    }
}

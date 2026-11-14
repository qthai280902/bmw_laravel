<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Events\AccessoryOrderCreated;
use App\Events\AppointmentCreated;
use App\Listeners\SendAccessoryOrderNotifications;
use App\Listeners\SendAppointmentNotifications;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class Phase23UserJourneyE2ETest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_discovery_filters_keyword_search_and_comparison_edge_cases(): void
    {
        $sedanCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $savCategory = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);
        $motoCategory = Category::factory()->create(['name' => 'Sport Bike', 'slug' => 'sport-bike']);
        $accessoryCategory = Category::factory()->create(['name' => 'Phụ kiện Ô tô', 'slug' => 'phu-kien-o-to']);

        $car330i = Product::factory()->for($sedanCategory)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_featured' => true,
            'is_active' => true,
            'specifications' => ['Engine' => '2.0L Turbo', 'Horsepower' => '258 HP'],
        ]);

        $carX3 = Product::factory()->for($savCategory)->create([
            'name' => 'BMW X3 M50 xDrive',
            'slug' => 'bmw-x3-m50-xdrive',
            'type' => VehicleType::CAR,
            'price' => 3999000000,
            'is_featured' => true,
            'is_active' => true,
            'specifications' => ['Engine' => '3.0L Inline-6', 'Horsepower' => '393 HP'],
        ]);

        $motoS1000 = Product::factory()->for($motoCategory)->create([
            'name' => 'BMW S1000RR',
            'slug' => 'bmw-s1000rr',
            'type' => VehicleType::MOTORBIKE,
            'price' => 949000000,
            'is_active' => true,
            'specifications' => ['Engine' => '999cc Inline-4', 'Horsepower' => '210 HP'],
        ]);

        $hiddenCar = Product::factory()->for($sedanCategory)->create([
            'name' => 'BMW 760i Prototype Hidden',
            'slug' => 'bmw-760i-prototype-hidden',
            'type' => VehicleType::CAR,
            'price' => 6500000000,
            'is_active' => false,
        ]);

        $deletedCar = Product::factory()->for($sedanCategory)->create([
            'name' => 'BMW M8 Soft Deleted',
            'slug' => 'bmw-m8-soft-deleted',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);
        $deletedCar->delete();

        $accessory = Product::factory()->for($accessoryCategory)->create([
            'name' => 'Thảm sàn BMW M Performance',
            'slug' => 'tham-san-bmw-m-performance',
            'type' => VehicleType::ACCESSORY,
            'price' => 8500000,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $car330i->id,
            'path' => 'images/cars/330i/hero-front-three-quarter.png',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        // 1. Homepage & Catalog browsing
        $this->get(route('home'))
            ->assertOk()
            ->assertSee($car330i->name)
            ->assertDontSee($hiddenCar->name);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('name="search"', false)
            ->assertSee($car330i->name)
            ->assertSee($carX3->name)
            ->assertDontSee($hiddenCar->name)
            ->assertDontSee($deletedCar->name);

        // 2. Motorbike & Accessories branch routes
        $this->get(route('products.index', ['type' => 'motorbike']))
            ->assertOk()
            ->assertSee($motoS1000->name)
            ->assertDontSee($car330i->name);

        $this->get(route('accessories.index'))
            ->assertOk()
            ->assertSee($accessory->name)
            ->assertDontSee($car330i->name);

        // 3. Keyword search + price range + category combined filtering
        $this->get(route('products.index', [
            'search' => 'X3 M50',
            'type' => 'car',
            'category_id' => $savCategory->id,
            'min_price' => 3500000000,
            'max_price' => 4500000000,
        ]))
            ->assertOk()
            ->assertSee($carX3->name)
            ->assertDontSee($car330i->name)
            ->assertDontSee($hiddenCar->name);

        // 4. Product detail visibility boundaries
        $this->get(route('products.show', $car330i->slug))
            ->assertOk()
            ->assertSee($car330i->name);

        $this->get(route('products.show', $hiddenCar->slug))->assertNotFound();
        $this->get(route('products.show', $deletedCar->slug))->assertNotFound();

        // 5. Vehicle Comparison handles duplicates, invalid IDs, accessories, inactive cars cleanly
        $this->get(route('products.compare', [
            'ids' => "{$car330i->id},{$car330i->id},invalid,999999,{$accessory->id},{$hiddenCar->id},{$carX3->id},{$motoS1000->id}",
        ]))
            ->assertOk()
            ->assertSee($car330i->name)
            ->assertSee($carX3->name)
            ->assertSee($motoS1000->name)
            ->assertDontSee($accessory->name)
            ->assertDontSee($hiddenCar->name);
    }

    public function test_all_five_booking_flows_for_guest_and_authenticated_user_with_validation_boundaries(): void
    {
        Event::fake([AppointmentCreated::class]);

        $sedanCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $accessoryCategory = Category::factory()->create(['name' => 'Phụ kiện Ô tô', 'slug' => 'phu-kien-o-to']);

        $car = Product::factory()->for($sedanCategory)->create([
            'name' => 'BMW 530i Sedan',
            'slug' => 'bmw-530i-sedan',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        $inactiveCar = Product::factory()->for($sedanCategory)->create([
            'name' => 'BMW Hidden Concept',
            'slug' => 'bmw-hidden-concept',
            'type' => VehicleType::CAR,
            'is_active' => false,
        ]);

        $accessory = Product::factory()->for($accessoryCategory)->create([
            'name' => 'BMW Dash Cam Pro',
            'slug' => 'bmw-dash-cam-pro',
            'type' => VehicleType::ACCESSORY,
            'is_active' => true,
        ]);

        $futureDate = now()->addDays(2)->format('Y-m-d H:i');

        // Booking page renders all modes cleanly including /booking?type=service
        $this->get(route('appointments.create', ['type' => 'service']))
            ->assertOk()
            ->assertSee('BMW Aftersales');

        // Flow 1: Guest Test Drive (+ XSS tag stripping check)
        $this->post(route('appointments.store'), [
            'guest_name' => '<script>alert(1)</script>Nguyen Van Khach',
            'guest_phone' => '0909 123 456',
            'guest_email' => 'khach1@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => $futureDate,
            'showroom' => 'Showroom Hà Nội',
            'notes' => '<b>Lái thử buổi sáng</b>',
        ])->assertRedirect(route('appointments.success'));

        $testDrive = Appointment::query()->where('type', AppointmentType::TestDrive->value)->firstOrFail();
        $this->assertSame('alert(1)Nguyen Van Khach', $testDrive->guest_name);
        $this->assertSame('Lái thử buổi sáng', $testDrive->notes);
        $this->assertSame('Showroom Hà Nội', $testDrive->showroom);
        $this->assertSame('Showroom Hà Nội', $testDrive->meta_data['showroom'] ?? null);

        // Flow 2: Guest Quote
        $this->post(route('appointments.store'), [
            'guest_name' => 'Tran Thi Bao Gia',
            'guest_phone' => '0909222333',
            'guest_email' => 'quote@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Quote->value,
            'appointment_date' => $futureDate,
            'meta_data' => ['payment_method' => 'Trả góp 70%'],
        ])->assertRedirect(route('appointments.success'));

        // Flow 3: Guest General Consult (without specific product_id)
        $this->post(route('appointments.store'), [
            'guest_name' => 'Le Tu Van Chung',
            'guest_phone' => '0909333444',
            'type' => AppointmentType::Consult->value,
            'appointment_date' => $futureDate,
            'notes' => 'Tư vấn dòng xe gia đình.',
        ])->assertRedirect(route('appointments.success'));

        $consult = Appointment::query()->where('type', AppointmentType::Consult->value)->firstOrFail();
        $this->assertNull($consult->product_id);

        // Flow 4: Guest Trade-In (matches trade-in-form.blade.php fields cleanly)
        $this->post(route('appointments.store'), [
            'guest_name' => 'Pham Thu Cu',
            'guest_phone' => '0909444555',
            'guest_email' => 'tradein@example.com',
            'type' => AppointmentType::TradeIn->value,
            'appointment_date' => $futureDate,
            'meta_data' => [
                'current_car' => 'Toyota Camry 2.5Q',
                'year' => '2022',
                'desired_bmw' => 'BMW 530i Sedan',
            ],
            'customer_car_model' => 'Toyota Camry 2.5Q',
            'customer_car_condition' => 'Odo 25.000 km',
        ])->assertRedirect(route('appointments.success'));

        $tradeIn = Appointment::query()->where('type', AppointmentType::TradeIn->value)->firstOrFail();
        $this->assertSame('Toyota Camry 2.5Q', $tradeIn->meta_data['current_car'] ?? null);
        $this->assertSame('2022', $tradeIn->meta_data['year'] ?? null);
        $this->assertSame('Odo 25.000 km', $tradeIn->meta_data['customer_car_condition'] ?? null);

        // Flow 5: Guest Service via /booking?type=service alias
        $this->post(route('appointments.store'), [
            'guest_name' => 'Hoang Bao Duong',
            'guest_phone' => '0909555666',
            'type' => 'service',
            'appointment_date' => $futureDate,
            'customer_car_model' => 'BMW 320i 2021',
            'customer_car_condition' => '30A-99999, 40.000km',
        ])->assertRedirect(route('appointments.success'));

        $serviceBooking = Appointment::query()->where('type', AppointmentType::Maintenance->value)->firstOrFail();
        $this->assertSame('service', $serviceBooking->meta_data['service_type'] ?? null);
        $this->assertSame('BMW 320i 2021', $serviceBooking->meta_data['customer_car_model'] ?? null);
        $this->assertSame('30A-99999, 40.000km', $serviceBooking->meta_data['customer_car_condition'] ?? null);

        Event::assertDispatchedTimes(AppointmentCreated::class, 5);

        // Verify Authenticated Customer auto-attaches user_id & contact info and can see /appointments
        $customer = User::factory()->create([
            'name' => 'Khach Hang Thanh Vien',
            'email' => 'member@example.com',
        ]);

        $this->actingAs($customer)
            ->post(route('appointments.store'), [
                'product_id' => $car->id,
                'type' => AppointmentType::TestDrive->value,
                'appointment_date' => $futureDate,
                'notes' => 'Đặt từ tài khoản thành viên',
            ])
            ->assertRedirect(route('appointments.success'));

        $memberAppointment = Appointment::query()->where('user_id', $customer->id)->firstOrFail();
        $this->assertSame('Khach Hang Thanh Vien', $memberAppointment->guest_name);
        $this->assertSame('member@example.com', $memberAppointment->guest_email);

        $this->actingAs($customer)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee($car->name)
            ->assertSee('Đặt từ tài khoản thành viên');

        // Verify Listener & Queue Notification payload without errors
        Mail::fake();
        (new SendAppointmentNotifications)->handle(new AppointmentCreated($memberAppointment));
        Mail::assertQueued(AppointmentConfirmationMail::class, fn ($mail) => $mail->hasTo('member@example.com') && $mail->queue === 'notifications');
        Mail::assertQueued(AppointmentAlertMail::class, fn ($mail) => $mail->queue === 'notifications');

        // Verify Validation Boundaries for Guests
        auth()->logout();

        // Past date + invalid email + invalid phone + missing required guest name
        $this->post(route('appointments.store'), [
            'guest_name' => '',
            'guest_phone' => 'not-a-phone',
            'guest_email' => '<script>x@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => now()->subDay()->format('Y-m-d H:i'),
        ])->assertSessionHasErrors(['guest_name', 'guest_phone', 'guest_email', 'appointment_date']);

        // Accessory or inactive product cannot be booked via appointments
        $this->post(route('appointments.store'), [
            'guest_name' => 'Tester',
            'guest_phone' => '0909123456',
            'product_id' => $accessory->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => $futureDate,
        ])->assertSessionHasErrors(['product_id']);

        $this->post(route('appointments.store'), [
            'guest_name' => 'Tester',
            'guest_phone' => '0909123456',
            'product_id' => $inactiveCar->id,
            'type' => AppointmentType::Quote->value,
            'appointment_date' => $futureDate,
        ])->assertSessionHasErrors(['product_id']);
    }

    public function test_accessory_order_flow_calculates_total_amount_and_enforces_product_boundaries(): void
    {
        Event::fake([AccessoryOrderCreated::class]);

        $accessoryCategory = Category::factory()->create(['name' => 'Phụ kiện Ô tô', 'slug' => 'phu-kien-o-to']);
        $carCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);

        $activeAccessory = Product::factory()->for($accessoryCategory)->create([
            'name' => 'BMW M Carbon Mirror Caps',
            'slug' => 'bmw-m-carbon-mirror-caps',
            'type' => VehicleType::ACCESSORY,
            'price' => 12000000,
            'is_active' => true,
        ]);

        $inactiveAccessory = Product::factory()->for($accessoryCategory)->create([
            'name' => 'Phụ kiện ngừng kinh doanh',
            'slug' => 'phu-kien-ngung-kinh-doanh',
            'type' => VehicleType::ACCESSORY,
            'price' => 5000000,
            'is_active' => false,
        ]);

        $vehicle = Product::factory()->for($carCategory)->create([
            'name' => 'BMW 330i Sedan',
            'slug' => 'bmw-330i-sedan',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        // Inactive accessory & vehicle must 404 on BOTH create & store routes
        $this->get(route('accessory-orders.create', $inactiveAccessory->slug))->assertNotFound();
        $this->post(route('accessory-orders.store', $inactiveAccessory->slug), [
            'customer_name' => 'Guest',
            'customer_phone' => '0909123456',
            'customer_address' => '1 Le Loi',
            'quantity' => 1,
        ])->assertNotFound();

        $this->get(route('accessory-orders.create', $vehicle->slug))->assertNotFound();
        $this->post(route('accessory-orders.store', $vehicle->slug), [
            'customer_name' => 'Guest',
            'customer_phone' => '0909123456',
            'customer_address' => '1 Le Loi',
            'quantity' => 1,
        ])->assertNotFound();

        // Guest order with quantity = 3 calculates total_amount = 36,000,000 & strips tags
        $this->post(route('accessory-orders.store', $activeAccessory->slug), [
            'customer_name' => 'Nguyen Phu Kien',
            'customer_phone' => '0909888777',
            'customer_address' => '<b>100 Nguyen Hue, Quan 1</b>',
            'customer_email' => 'phukien@example.com',
            'quantity' => 3,
            'notes' => '<script>xss()</script>Giao buổi chiều',
        ])->assertRedirect(route('accessory-orders.create', $activeAccessory->slug));

        $order = AccessoryOrder::query()->with('product')->firstOrFail();
        $this->assertSame(36000000, $order->total_amount);
        $this->assertSame('100 Nguyen Hue, Quan 1', $order->customer_address);
        $this->assertSame('xss()Giao buổi chiều', $order->notes);

        // Verify mail rendering & queue
        Mail::fake();
        (new SendAccessoryOrderNotifications)->handle(new AccessoryOrderCreated($order));
        Mail::assertQueued(AccessoryOrderConfirmationMail::class, function (AccessoryOrderConfirmationMail $mail): bool {
            return $mail->hasTo('phukien@example.com')
                && Str::contains($mail->render(), '36.000.000');
        });
        Mail::assertQueued(AccessoryOrderAlertMail::class, fn ($mail) => $mail->queue === 'notifications');

        // Authenticated customer sees pre-filled contact fields and can submit relying on fallback
        $customer = User::factory()->create([
            'name' => 'Customer Accessory Buyer',
            'email' => 'buyer@example.com',
        ]);

        $this->actingAs($customer)
            ->get(route('accessory-orders.create', $activeAccessory->slug))
            ->assertOk()
            ->assertSee('Customer Accessory Buyer', false)
            ->assertSee('buyer@example.com', false);

        $this->actingAs($customer)
            ->post(route('accessory-orders.store', $activeAccessory->slug), [
                'customer_phone' => '0911222333',
                'customer_address' => '200 Hai Ba Trung',
                'quantity' => 2,
            ])
            ->assertRedirect(route('accessory-orders.create', $activeAccessory->slug));

        $this->assertDatabaseHas(AccessoryOrder::class, [
            'product_id' => $activeAccessory->id,
            'customer_name' => 'Customer Accessory Buyer',
            'customer_email' => 'buyer@example.com',
            'quantity' => 2,
        ]);

        // Quantity <= 0 and invalid phone fail validation
        $this->post(route('accessory-orders.store', $activeAccessory->slug), [
            'customer_name' => 'Bad Qty',
            'customer_phone' => 'abc',
            'customer_address' => 'Addr',
            'customer_email' => '<invalid@example.com>',
            'quantity' => 0,
        ])->assertSessionHasErrors(['customer_phone', 'customer_email', 'quantity']);
    }

    public function test_editorial_articles_and_offers_enforce_publication_visibility(): void
    {
        $publishedArticle = Article::factory()->create([
            'title' => 'Đánh giá BMW i4 M60 thực tế',
            'slug' => 'danh-gia-bmw-i4-m60-thuc-te',
            'category' => Article::CATEGORY_BMW_EXPERIENCE,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $draftArticle = Article::factory()->create([
            'title' => 'Bài nháp chưa xuất bản',
            'slug' => 'bai-nhap-chua-xuat-ban',
            'category' => Article::CATEGORY_SHOWROOM_NEWS,
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $futureArticle = Article::factory()->create([
            'title' => 'Bài hẹn giờ tương lai',
            'slug' => 'bai-hen-gio-tuong-lai',
            'category' => Article::CATEGORY_SHOWROOM_NEWS,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->addDays(3),
        ]);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee($publishedArticle->title)
            ->assertDontSee($draftArticle->title)
            ->assertDontSee($futureArticle->title);

        $this->get(route('articles.index', ['category' => Article::CATEGORY_BMW_EXPERIENCE]))
            ->assertOk()
            ->assertSee($publishedArticle->title);

        $this->get(route('articles.show', $publishedArticle->slug))
            ->assertOk()
            ->assertSee($publishedArticle->title);

        $this->get(route('articles.show', $draftArticle->slug))->assertNotFound();
        $this->get(route('articles.show', $futureArticle->slug))->assertNotFound();

        // Static / experiential / offer pages accessible to Guest & Real User
        foreach (['offers.exclusive', 'services.index', 'experiences.index', 'contact.index', 'policy.privacy'] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_ai_showroom_assistant_rag_context_and_conversation_reset_e2e(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.gemini_keys.primary' => 'AIzaPhase23FakeKey1234567890',
        ]);

        $savCategory = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);
        $vehicle = Product::factory()->for($savCategory)->create([
            'name' => 'BMW X3 M50 xDrive',
            'slug' => 'bmw-x3-m50-xdrive',
            'type' => VehicleType::CAR,
            'price' => 3999000000,
            'is_active' => true,
        ]);

        ShowroomAssistant::fake([
            'Mẫu BMW X3 M50 xDrive được bảo hành chính hãng theo tiêu chuẩn BMW. Quý khách có thể [Đăng ký lái thử](/booking?type=test_drive).',
            'Cuộc trò chuyện mới đã sẵn sàng hỗ trợ Quý khách.',
        ]);

        $visitorId = 'phase23-e2e-visitor-01';

        $askResponse = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Mẫu xe này bảo hành mấy năm?',
            'visitor_id' => $visitorId,
            'product_slug' => $vehicle->slug,
            'page_url' => route('products.show', $vehicle->slug),
        ])->assertOk();

        $askResponse
            ->assertJsonPath('status', 'ok');

        $this->assertArrayNotHasKey('current_product', $askResponse->json());
        $this->assertStringContainsString('/booking?type=test_drive', json_encode($askResponse->json('suggestions')));

        // Prompt includes grounded RAG chunk + current product context
        ShowroomAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => Str::contains($prompt->prompt, '[TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC]')
            && Str::contains($prompt->prompt, 'BMW X3 M50 xDrive'));

        // Synced into AiChatSession for Admin CRM
        $session = AiChatSession::query()->where('visitor_id', $visitorId)->firstOrFail();
        $this->assertSame($vehicle->id, $session->context_product_id);
        $this->assertSame($vehicle->slug, $session->context_product_slug);

        // Reset keeps CRM logs intact while clearing active conversation turns
        $this->postJson(route('ai.showroom-assistant.reset'), [
            'visitor_id' => $visitorId,
        ])->assertOk()->assertJson(['status' => 'ok', 'reset' => true]);

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Bắt đầu câu hỏi mới',
            'visitor_id' => $visitorId,
        ])->assertOk();

        $this->assertSame(4, $session->messages()->count());
    }

    public function test_rbac_boundaries_and_public_to_admin_crm_synchronization(): void
    {
        $customer = User::factory()->create([
            'name' => 'Normal Customer',
            'email' => 'customer.phase23@example.com',
        ]);

        $admin = User::factory()->create([
            'name' => 'Showroom Admin',
            'email' => 'admin.phase23@bmw.com',
        ]);

        $accessoryCategory = Category::factory()->create(['name' => 'Phụ kiện Ô tô', 'slug' => 'phu-kien-o-to']);
        $sedanCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);

        $car = Product::factory()->for($sedanCategory)->create([
            'name' => 'BMW 330i Sedan',
            'slug' => 'bmw-330i-sedan',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        $accessory1 = Product::factory()->for($accessoryCategory)->create([
            'name' => 'Thảm cốp BMW X3',
            'slug' => 'tham-cop-bmw-x3',
            'type' => VehicleType::ACCESSORY,
            'price' => 4500000,
            'is_active' => true,
        ]);

        $accessory2 = Product::factory()->for($accessoryCategory)->create([
            'name' => 'Sạc BMW Wallbox',
            'slug' => 'sac-bmw-wallbox',
            'type' => VehicleType::ACCESSORY,
            'price' => 25000000,
            'is_active' => true,
        ]);

        // Seed public leads & orders
        Appointment::create([
            'user_id' => $customer->id,
            'guest_name' => $customer->name,
            'guest_email' => $customer->email,
            'guest_phone' => '0909111222',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Pending,
            'showroom' => 'Showroom TP. HCM',
            'meta_data' => ['showroom' => 'Showroom TP. HCM'],
        ]);

        Appointment::create([
            'guest_name' => 'Guest TradeIn CRM',
            'guest_email' => 'guest.tradein@example.com',
            'guest_phone' => '0909222333',
            'product_id' => null,
            'type' => AppointmentType::TradeIn,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
            'meta_data' => ['current_car' => 'Mercedes C200', 'desired_bmw' => 'BMW 330i Sedan'],
        ]);

        $orders = [];
        for ($i = 1; $i <= 5; $i++) {
            $product = $i % 2 === 0 ? $accessory2 : $accessory1;
            $orders[] = AccessoryOrder::create([
                'product_id' => $product->id,
                'customer_name' => "Customer Order {$i}",
                'customer_phone' => "090900000{$i}",
                'customer_address' => "{$i} Nguyen Hue",
                'customer_email' => "order{$i}@example.com",
                'quantity' => $i,
                'status' => AccessoryOrder::STATUS_PENDING,
            ]);
        }

        $aiSession = AiChatSession::create([
            'visitor_id' => 'crm-sync-visitor',
            'display_name' => 'Khách AI CRM',
            'context_product_id' => $car->id,
            'context_product_name' => $car->name,
            'context_product_slug' => $car->slug,
            'first_seen_at' => now()->subMinutes(10),
            'last_seen_at' => now(),
            'message_count' => 2,
            'last_intent' => 'sedan',
        ]);

        // 1. Guest redirects to login for auth/admin routes
        foreach (['/appointments', '/profile', '/dashboard', '/admin/products', '/admin/appointments', '/admin/accessory-orders', '/admin/ai-conversations'] as $protectedUri) {
            $this->get($protectedUri)->assertRedirect('/login');
        }

        // 2. Normal Customer can open personal routes, forbidden from ALL /dashboard & /admin/*
        $this->actingAs($customer)->get(route('appointments.index'))->assertOk();
        $this->actingAs($customer)->get(route('profile.edit'))->assertOk();

        foreach ([
            '/dashboard',
            '/admin/products',
            '/admin/categories',
            '/admin/articles',
            '/admin/appointments',
            '/admin/accessory-orders',
            '/admin/ai-conversations',
            '/admin/customers',
            '/admin/users',
            '/admin/site-settings',
        ] as $adminUri) {
            $this->actingAs($customer)->get($adminUri)->assertForbidden();
        }

        // 3. Admin views all public data synced cleanly + Zero N+1 on AccessoryOrders index
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Lead Analytics')
            ->assertSeeText('Guest TradeIn CRM')
            ->assertSeeText('Normal Customer');

        $this->actingAs($admin)
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSeeText('Guest TradeIn CRM')
            ->assertSeeText('Normal Customer');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $accessoryIndexResponse = $this->actingAs($admin)
            ->get(route('admin.accessory-orders.index'))
            ->assertOk()
            ->assertSeeText('Customer Order 5')
            ->assertSeeText('22,500,000 VNĐ');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count queries (COUNT + paginator select + eager-loaded products + eager-loaded categories <= 6)
        $this->assertLessThanOrEqual(8, count($queries), 'Expected eager-loaded AccessoryOrders without N+1 queries.');
        $accessoryIndexResponse->assertSeeText('100,000,000 VNĐ');

        $this->actingAs($admin)
            ->get(route('admin.accessory-orders.show', $orders[4]))
            ->assertOk()
            ->assertSeeText('22,500,000');

        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.index'))
            ->assertOk()
            ->assertSeeText($car->name);

        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.show', $aiSession))
            ->assertOk()
            ->assertSeeText($car->name);

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSeeText('Normal Customer');
    }
}

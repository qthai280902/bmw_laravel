<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase28_5UiPolishAndDistinctMailsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(string $name = 'Nguyễn Quản Trị', string $role = 'admin'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => uniqid('staff_', true).'@bmw.com',
            'role' => $role,
        ]);
    }

    private function createCarProduct(): Product
    {
        $category = Category::factory()->create([
            'name' => 'BMW Sedan',
            'slug' => 'bmw-sedan-'.uniqid(),
        ]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 2759000000,
        ]);
    }

    private function createAccessoryProduct(): Product
    {
        $category = Category::factory()->create([
            'name' => 'Phụ kiện M Performance',
            'slug' => 'phu-kien-m-'.uniqid(),
        ]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Thảm sàn BMW chính hãng',
            'slug' => 'tham-san-bmw-'.uniqid(),
            'type' => VehicleType::ACCESSORY,
            'price' => 4500000,
        ]);
    }

    public function test_crm_tables_stay_within_8_sql_queries_on_cold_cache_and_render_eye_buttons_and_quick_filters(): void
    {
        $admin = $this->createAdminUser();
        $car = $this->createCarProduct();
        $accessory = $this->createAccessoryProduct();

        $appointment = Appointment::create([
            'guest_name' => 'Trần Minh Khôi',
            'guest_phone' => '0909123456',
            'guest_email' => 'khoi@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
            'notes' => 'Muốn lái thử chiều cuối tuần.',
        ]);

        $serviceBooking = Appointment::create([
            'guest_name' => 'Lê Bảo Ngọc',
            'guest_phone' => '0912345678',
            'guest_email' => 'ngoc@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDays(3),
            'status' => AppointmentStatus::Pending,
            'notes' => 'Bảo dưỡng 10.000km.',
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Phạm Quốc Huy',
            'customer_phone' => '0988776655',
            'customer_email' => 'huy@example.com',
            'customer_address' => '123 Nguyễn Văn Linh, Quận 7, TP.HCM',
            'quantity' => 1,
            'unit_price' => 4500000,
            'total_price' => 4500000,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $routes = [
            [
                'url' => route('admin.appointments.index'),
                'eyeTestId' => 'data-testid="inspect-appointment-'.$appointment->id.'"',
                'quickFilter' => "applyQuickFilter('pending')",
            ],
            [
                'url' => route('admin.service-bookings.index'),
                'eyeTestId' => 'data-testid="inspect-service-booking-'.$serviceBooking->id.'"',
                'quickFilter' => "applyQuickFilter('pending')",
            ],
            [
                'url' => route('admin.accessory-orders.index'),
                'eyeTestId' => 'data-testid="inspect-accessory-order-'.$order->id.'"',
                'quickFilter' => "applyQuickFilter('pending')",
            ],
        ];

        foreach ($routes as $item) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $response = $this->actingAs($admin)->get($item['url']);
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $response->assertOk();
            $response->assertSee($item['eyeTestId'], false);
            $response->assertSee($item['quickFilter'], false);
            $response->assertDontSee('min-w-[1140px]', false);
            $response->assertDontSee('min-w-[980px]', false);

            $this->assertLessThanOrEqual(
                8,
                count($queries),
                "Cold-cache SQL query budget exceeded on {$item['url']}: ".count($queries).' queries.'
            );
        }
    }

    public function test_quick_inspector_drawer_and_quick_filters_work_on_all_three_crm_livewire_tables(): void
    {
        $admin = $this->createAdminUser('Hoàng Cố Vấn', 'sales');
        $car = $this->createCarProduct();
        $accessory = $this->createAccessoryProduct();

        $hotAppointment = Appointment::create([
            'guest_name' => 'Khách VIP Hot',
            'guest_phone' => '0901112222',
            'guest_email' => 'vip@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Pending,
            'notes' => 'Quan tâm tài chính trả góp.',
        ]);
        $hotAppointment->forceFill(['lead_priority' => Appointment::LEAD_PRIORITY_HOT])->saveQuietly();

        $completedAppointment = Appointment::create([
            'guest_name' => 'Khách Đã Xong',
            'guest_phone' => '0903334444',
            'guest_email' => 'done@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now()->addDays(4),
            'status' => AppointmentStatus::Completed,
            'lead_priority' => Appointment::LEAD_PRIORITY_NORMAL,
        ]);

        $serviceBooking = Appointment::create([
            'guest_name' => 'Khách Detailing',
            'guest_phone' => '0919998888',
            'guest_email' => 'detailing@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Detailing,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
            'notes' => 'Phủ ceramic toàn xe.',
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Phụ Kiện',
            'customer_phone' => '0977665544',
            'customer_email' => 'order@example.com',
            'customer_address' => '456 Lê Lợi, Quận 1, TP.HCM',
            'quantity' => 2,
            'unit_price' => 4500000,
            'total_price' => 9000000,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('applyQuickFilter', 'hot')
            ->assertSet('leadPriority', Appointment::LEAD_PRIORITY_HOT)
            ->assertSee('Khách VIP Hot')
            ->assertDontSee('Khách Đã Xong')
            ->call('inspectAppointment', $hotAppointment->id)
            ->assertSet('inspectedAppointmentId', $hotAppointment->id)
            ->assertSee('HỒ SƠ LỊCH HẸN #'.$hotAppointment->id)
            ->assertSee('Duyệt & Xác nhận')
            ->assertSee('Hủy hồ sơ')
            ->assertSee('Quan tâm tài chính trả góp.')
            ->call('closeInspector')
            ->assertSet('inspectedAppointmentId', null);

        Livewire::actingAs($admin)
            ->test(ServiceBookingTable::class)
            ->call('applyQuickFilter', 'pending')
            ->assertSet('status', AppointmentStatus::Pending->value)
            ->call('inspectBooking', $serviceBooking->id)
            ->assertSet('inspectedBookingId', $serviceBooking->id)
            ->assertSee('DỊCH VỤ CHĂM SÓC XE #'.$serviceBooking->id)
            ->assertSee('Phủ ceramic toàn xe.')
            ->call('closeInspector')
            ->assertSet('inspectedBookingId', null);

        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->call('applyQuickFilter', 'pending')
            ->assertSet('status', AccessoryOrder::STATUS_PENDING)
            ->call('inspectOrder', $order->id)
            ->assertSet('inspectedOrderId', $order->id)
            ->assertSee('ĐƠN PHỤ KIỆN #ACC-'.$order->id)
            ->assertSee('Thảm sàn BMW chính hãng')
            ->call('closeInspector')
            ->assertSet('inspectedOrderId', null);

        $this->assertNotNull($completedAppointment->id);
    }

    public function test_public_booking_validation_uses_vietnamese_messages_for_appointment_date(): void
    {
        $car = $this->createCarProduct();

        $missingDateResponse = $this->post(route('appointments.store'), [
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'guest_name' => 'Nguyễn Văn A',
            'guest_phone' => '0901234567',
            'guest_email' => 'vana@example.com',
            'appointment_date' => '',
        ]);

        $missingDateResponse->assertSessionHasErrors('appointment_date');
        $missingMessage = (string) session('errors')->first('appointment_date');
        $this->assertSame('Vui lòng chọn ngày và giờ dự kiến.', $missingMessage);
        $this->assertStringNotContainsStringIgnoringCase('appointment date', $missingMessage);

        $garbageAutofillResponse = $this->post(route('appointments.store'), [
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'guest_name' => 'Nguyễn Văn A',
            'guest_phone' => '0901234567',
            'guest_email' => 'vana@example.com',
            'appointment_date' => 'vana@example.com',
        ]);

        $garbageAutofillResponse->assertSessionHasErrors('appointment_date');
        $garbageMessage = (string) session('errors')->first('appointment_date');
        $this->assertStringContainsString('Ngày và giờ dự kiến', $garbageMessage);
        $this->assertStringNotContainsStringIgnoringCase('appointment date', $garbageMessage);
    }

    public function test_two_appointment_email_modes_are_distinct_and_confirming_dispatches_official_mode(): void
    {
        Mail::fake();

        $sales = $this->createAdminUser('Trần Minh Đức', 'sales');
        $car = $this->createCarProduct();

        $appointment = Appointment::create([
            'guest_name' => 'Võ Gia Bảo',
            'guest_phone' => '0908889999',
            'guest_email' => 'giabao@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'status' => AppointmentStatus::Pending,
        ]);

        $systemMail = new AppointmentConfirmationMail(
            $appointment,
            AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED
        );
        $officialMail = new AppointmentConfirmationMail(
            $appointment,
            AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
            'Trần Minh Đức',
            'Chuyên viên Tư vấn BMW'
        );

        $this->assertSame(
            "[BMW Hệ Thống] Đã ghi nhận yêu cầu #{$appointment->id} — Chờ chuyên viên xác nhận",
            $systemMail->envelope()->subject
        );
        $this->assertSame(
            "[BMW Concierge] XÁC NHẬN CHÍNH THỨC lịch hẹn #{$appointment->id} — Thẻ đón tiếp VIP",
            $officialMail->envelope()->subject
        );
        $this->assertSame(
            AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED,
            $systemMail->headers()->text['X-BMW-Mail-Mode'] ?? null
        );
        $this->assertSame(
            AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
            $officialMail->headers()->text['X-BMW-Mail-Mode'] ?? null
        );

        $systemHtml = $systemMail->render();
        $officialHtml = $officialMail->render();

        $this->assertStringContainsString('ĐÃ TIẾP NHẬN', $systemHtml);
        $this->assertStringContainsString('Yêu cầu của Quý khách đã được Hệ thống ghi nhận', $systemHtml);
        $this->assertStringNotContainsString('VIP CONCIERGE PASS', $systemHtml);
        $this->assertStringNotContainsStringIgnoringCase('monospace', $systemHtml);
        $this->assertStringNotContainsStringIgnoringCase('courier', $systemHtml);

        $this->assertStringContainsString('ĐÃ XÁC NHẬN CHÍNH THỨC', $officialHtml);
        $this->assertStringContainsString('VIP CONCIERGE PASS', $officialHtml);
        $this->assertStringContainsString('Lịch hẹn của Quý khách đã được Chuyên viên BMW xác nhận', $officialHtml);
        $this->assertStringContainsString('Trần Minh Đức', $officialHtml);
        $this->assertStringContainsString('Chuyên viên Tư vấn BMW', $officialHtml);
        $this->assertStringNotContainsStringIgnoringCase('monospace', $officialHtml);
        $this->assertStringNotContainsStringIgnoringCase('courier', $officialHtml);

        $previewSystem = $this->actingAs($sales)->get(route('admin.mail-preview.appointment', [
            'appointment' => $appointment,
            'mode' => AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED,
        ]));
        $previewSystem->assertOk();
        $previewSystem->assertSee('Yêu cầu của Quý khách đã được Hệ thống ghi nhận', false);

        $previewOfficial = $this->actingAs($sales)->get(route('admin.mail-preview.appointment', [
            'appointment' => $appointment,
            'mode' => AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
        ]));
        $previewOfficial->assertOk();
        $previewOfficial->assertSee('Lịch hẹn của Quý khách đã được Chuyên viên BMW xác nhận', false);
        $previewOfficial->assertSee('VIP CONCIERGE PASS', false);

        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('updateStatus', $appointment->id, AppointmentStatus::Confirmed->value);

        Mail::assertQueued(AppointmentConfirmationMail::class, function (AppointmentConfirmationMail $mail) use ($appointment): bool {
            return $mail->appointment->id === $appointment->id
                && $mail->mode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED
                && $mail->confirmedByName === 'Trần Minh Đức'
                && $mail->hasTo('giabao@example.com');
        });
    }

    public function test_app_css_contains_dark_autofill_override_and_slim_dark_luxury_scrollbar(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString(':-webkit-autofill', $css);
        $this->assertStringContainsString('#0b0f17', strtolower($css));
        $this->assertStringContainsString('::-webkit-scrollbar', $css);
        $this->assertStringContainsString('#1c69d4', strtolower($css));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
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

class Phase28_6DrawerTimelineAndSixMailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(string $name = 'Nguyễn Quốc Thái', string $role = User::ROLE_ADMIN): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => uniqid('staff_', true).'@bmw.com',
            'role' => $role,
        ]);
    }

    private function createCar(): Product
    {
        $category = Category::factory()->create([
            'name' => 'BMW Sedan',
            'slug' => 'bmw-sedan-'.uniqid(),
        ]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 740i Pure Excellence',
            'slug' => 'bmw-740i-pure-excellence-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 5369000000,
        ]);
    }

    public function test_drawer_uses_body_teleport_z100_independent_scroll_and_renders_two_way_timeline_within_8_queries(): void
    {
        Mail::fake();

        $admin = $this->createStaff('Nguyễn Quốc Thái', User::ROLE_ADMIN);
        $car = $this->createCar();

        $originalDate = now()->addDays(2)->setTime(9, 30);
        $rescheduledDate = now()->addDays(4)->setTime(14, 0);

        // 1. Khách hàng đặt lịch
        $appointment = Appointment::create([
            'guest_name' => 'Đỗ Minh Quân',
            'guest_phone' => '0909888777',
            'guest_email' => 'quan.do@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => $originalDate,
            'status' => AppointmentStatus::Pending,
            'notes' => 'Muốn trải nghiệm cao tốc Long Thành.',
        ]);

        // 2. Khách hàng tự đổi lịch qua Signed URL
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => $rescheduledDate->format('Y-m-d H:i'),
            'reason' => 'Bận họp đột xuất buổi sáng.',
        ])->assertRedirect();

        // 3. Admin tiếp nhận lịch khách vừa đổi -> tự chuyển sang Confirmed, gửi VIP Pass + ghi Timeline
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('acknowledgeGuestReschedule', $appointment->id)
            ->call('inspectAppointment', $appointment->id)
            ->assertSee('x-teleport="body"', false)
            ->assertSee('z-[100]', false)
            ->assertSee('z-[101]', false)
            ->assertSee('h-dvh', false)
            ->assertSee('overflow-y-auto overscroll-contain', false)
            ->assertSee('Lịch sử tương tác Khách hàng ↔ Hệ thống')
            ->assertSee('KHÁCH HÀNG')
            ->assertSee('HỆ THỐNG TỰ ĐỘNG')
            ->assertSee('ADMIN / SHOWROOM')
            ->assertSee('Khách tự đổi ngày/giờ hẹn')
            ->assertSee($originalDate->format('H:i — d/m/Y').' → '.$rescheduledDate->format('H:i — d/m/Y'))
            ->assertSee('Quản Lý · Nguyễn Quốc Thái')
            ->assertSee('Xem thư Hệ thống tiếp nhận')
            ->assertSee('Xem thư Admin duyệt VIP')
            ->assertSee('Xem thư Khách đổi lịch')
            ->assertSee('Xem thư Nhắc lịch 24h')
            ->assertSee('Xem thư Hậu mãi trả góp');

        // 4. Cold-cache query budget <= 8 queries trên /admin/appointments
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)->get(route('admin.appointments.index'))->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(8, count($queries), 'Expected <= 8 SQL queries on /admin/appointments, got '.count($queries));
    }

    public function test_guest_reschedule_dispatches_guest_change_mail_with_diff_table_ics_and_plain_text(): void
    {
        Mail::fake();

        $car = $this->createCar();
        $originalDate = now()->addDays(2)->setTime(10, 0);
        $newDate = now()->addDays(5)->setTime(15, 30);

        $appointment = Appointment::create([
            'guest_name' => 'Lê Thảo Vy',
            'guest_phone' => '0911223344',
            'guest_email' => 'thaovy@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => $originalDate,
            'status' => AppointmentStatus::Pending,
        ]);

        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => $newDate->format('Y-m-d H:i'),
            'reason' => 'Chuyển sang chiều thứ Sáu cho thuận tiện.',
        ])->assertRedirect();

        Mail::assertQueued(GuestAppointmentChangeMail::class, function (GuestAppointmentChangeMail $mail) use ($appointment, $originalDate, $newDate): bool {
            $html = $mail->render();

            $this->assertSame(
                "[BMW Showroom] Khách hàng điều chỉnh giờ hẹn — Mã {$appointment->publicReferenceCode()}",
                $mail->envelope()->subject
            );
            $this->assertStringContainsString('#D97706', $html);
            $this->assertStringContainsString('Khách hàng cập nhật', $html);
            $this->assertStringContainsString($originalDate->format('H:i — d/m/Y'), $html);
            $this->assertStringContainsString($newDate->format('H:i — d/m/Y'), $html);
            $this->assertStringContainsString('Chuyển sang chiều thứ Sáu cho thuận tiện.', $html);
            $this->assertStringNotContainsStringIgnoringCase('monospace', $html);

            $mail->assertSeeInText($originalDate->format('H:i — d/m/Y'));
            $mail->assertSeeInText($newDate->format('H:i — d/m/Y'));
            $this->assertCount(1, $mail->attachments());

            return $mail->appointment->id === $appointment->id
                && $mail->hasTo('thaovy@example.com');
        });
    }

    public function test_mail_preview_renders_all_five_customer_email_modes_and_internal_crm_alerts_are_distinct(): void
    {
        $admin = $this->createStaff('Trần Quản Lý', User::ROLE_ADMIN);
        $car = $this->createCar();

        $appointment = Appointment::create([
            'guest_name' => 'Phạm Anh Tuấn',
            'guest_phone' => '0907654321',
            'guest_email' => 'tuan.pham@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(11, 0),
            'status' => AppointmentStatus::Pending,
        ]);

        $previewCases = [
            'system_received' => [
                'accent' => '#1C69D4',
                'badge' => 'Hệ thống tự động',
                'mustContain' => 'Yêu cầu của Quý khách đã được Hệ thống ghi nhận',
            ],
            'admin_confirmed' => [
                'accent' => '#059669',
                'badge' => 'Chuyên viên xác nhận',
                'mustContain' => 'VIP CONCIERGE PASS',
            ],
            'guest_rescheduled' => [
                'accent' => '#D97706',
                'badge' => 'Khách hàng cập nhật',
                'mustContain' => 'BẢNG ĐỐI CHIẾU THÔNG TIN TRƯỚC',
            ],
            'reminder' => [
                'accent' => '#4F46E5',
                'badge' => 'Nhắc lịch 24h',
                'mustContain' => 'Bằng lái xe hạng B',
            ],
            'followup' => [
                'accent' => '#B45309',
                'badge' => 'Chăm sóc hậu mãi',
                'mustContain' => 'BẢNG ƯỚC TÍNH TÀI CHÍNH',
            ],
        ];

        foreach ($previewCases as $mode => $expected) {
            $response = $this->actingAs($admin)->get(route('admin.mail-preview.appointment', [
                'appointment' => $appointment,
                'mode' => $mode,
            ]));

            $response->assertOk();
            $content = (string) $response->getContent();
            $this->assertStringContainsString($expected['accent'], $content, "Missing accent {$expected['accent']} for mode {$mode}");
            $this->assertStringContainsString($expected['badge'], $content, "Missing badge {$expected['badge']} for mode {$mode}");
            $this->assertStringContainsString($expected['mustContain'], $content, "Missing content for mode {$mode}");
            $this->assertStringNotContainsStringIgnoringCase('monospace', $content, "Found monospace in mode {$mode}");
            $this->assertStringNotContainsStringIgnoringCase('courier', $content, "Found courier in mode {$mode}");
        }

        // Kiểm tra Mẫu 4 & 5 Subject
        $reminderMail = new AppointmentReminderMail($appointment);
        $this->assertSame(
            "[BMW Nhắc Lịch] Còn 24 giờ tới lịch hẹn tại Showroom — Mã {$appointment->publicReferenceCode()}",
            $reminderMail->envelope()->subject
        );

        $followupMail = new PostTestDriveFollowupMail($appointment);
        $this->assertSame(
            "[BMW Concierge] Cảm ơn Quý khách & Bảng dự toán tài chính {$car->name} — Mã {$appointment->publicReferenceCode()}",
            $followupMail->envelope()->subject
        );

        // Kiểm tra Mẫu 6 (Cảnh báo nội bộ CRM)
        $hotLeadMail = new HotLeadAlertMail($appointment);
        $hotHtml = $hotLeadMail->render();
        $this->assertStringStartsWith('[HOT LEAD]', (string) $hotLeadMail->envelope()->subject);
        $this->assertStringContainsString('#DC2626', $hotHtml);
        $this->assertStringContainsString('Nội bộ CRM', $hotHtml);
        $this->assertStringNotContainsStringIgnoringCase('monospace', $hotHtml);

        $alertMail = new AppointmentAlertMail($appointment);
        $alertHtml = $alertMail->render();
        $this->assertStringStartsWith('[BMW Showroom] Yêu cầu đặt lịch mới:', (string) $alertMail->envelope()->subject);
        $this->assertStringContainsString('#DC2626', $alertHtml);
        $this->assertStringContainsString('Nội bộ CRM', $alertHtml);
        $this->assertStringNotContainsStringIgnoringCase('monospace', $alertHtml);
    }

    public function test_legacy_records_without_timeline_json_render_fallback_timeline_on_service_and_accessory_drawers(): void
    {
        $admin = $this->createStaff();
        $car = $this->createCar();

        $accessoryCat = Category::factory()->create([
            'name' => 'Phụ kiện',
            'slug' => 'phu-kien-'.uniqid(),
        ]);
        $accessory = Product::factory()->create([
            'category_id' => $accessoryCat->id,
            'name' => 'Camera hành trình BMW ACE 3.0',
            'slug' => 'bmw-ace-3-'.uniqid(),
            'type' => VehicleType::ACCESSORY,
            'price' => 11500000,
        ]);

        // Tạo bản ghi legacy với meta_data = null (không có timeline)
        $serviceBooking = Appointment::create([
            'guest_name' => 'Khách Dịch Vụ Legacy',
            'guest_phone' => '0933445566',
            'guest_email' => 'legacy.service@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);
        $serviceBooking->forceFill([
            'meta_data' => null,
            'confirmation_email_sent_at' => now()->subHour(),
        ])->saveQuietly();

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Phụ Kiện Legacy',
            'customer_phone' => '0944556677',
            'customer_email' => 'legacy.order@example.com',
            'customer_address' => '88 Đồng Khởi, Quận 1, TP.HCM',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_CONFIRMED,
            'confirmed_at' => now()->subMinutes(15),
        ]);

        Livewire::actingAs($admin)
            ->test(ServiceBookingTable::class)
            ->call('inspectBooking', $serviceBooking->id)
            ->assertSee('x-teleport="body"', false)
            ->assertSee('Lịch sử tương tác Khách hàng ↔ Hệ thống')
            ->assertSee('Gửi thư tiếp nhận ban đầu')
            ->assertSee('Xem thư Khách đổi lịch');

        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->call('inspectOrder', $order->id)
            ->assertSee('x-teleport="body"', false)
            ->assertSee('Lịch sử tương tác Khách hàng ↔ Hệ thống')
            ->assertSee('Đặt đơn phụ kiện #ACC-'.$order->id)
            ->assertSee('Xác nhận đơn phụ kiện');
    }
}

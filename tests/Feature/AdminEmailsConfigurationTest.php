<?php

namespace Tests\Feature;

use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Mail\Admin\HotLeadAlertMail;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\NewAppointmentLeadNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_behavior_accepts_only_bmw_domain_when_admin_emails_empty(): void
    {
        config(['auth.admin_emails' => []]);

        $bmwAdmin = User::factory()->create(['email' => 'thai@bmw.com']);
        $gmailGuest = User::factory()->create(['email' => 'guest@gmail.com']);

        $this->assertTrue($bmwAdmin->isAdmin());
        $this->assertFalse($gmailGuest->isAdmin());

        $adminIds = User::admins()->pluck('id')->all();
        $this->assertContains($bmwAdmin->id, $adminIds);
        $this->assertNotContains($gmailGuest->id, $adminIds);

        $this->actingAs($bmwAdmin)
            ->get(route('admin.appointments.index'))
            ->assertOk();

        $this->actingAs($gmailGuest)
            ->get(route('admin.appointments.index'))
            ->assertForbidden();
    }

    public function test_allowlisted_non_bmw_email_grants_admin_access_case_insensitively(): void
    {
        config(['auth.admin_emails' => ['thaibanana2k2@gmail.com']]);

        $allowlistedUser = User::factory()->create(['email' => 'ThaiBanana2k2@Gmail.com']);

        $this->assertTrue($allowlistedUser->isAdmin());
        $this->assertTrue(User::admins()->whereKey($allowlistedUser->id)->exists());

        $this->actingAs($allowlistedUser)
            ->get(route('admin.appointments.index'))
            ->assertOk();
    }

    public function test_hot_lead_notification_and_alert_mail_are_delivered_to_allowlisted_gmail_admin(): void
    {
        Mail::fake();
        Cache::flush();

        config(['auth.admin_emails' => ['thaibanana2k2@gmail.com']]);

        $gmailAdmin = User::factory()->create(['email' => 'thaibanana2k2@gmail.com']);
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan-admin-emails']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport-admin-emails',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_active' => true,
        ]);

        $this->post(route('appointments.store'), [
            'guest_name' => 'Tran Van VIP',
            'guest_phone' => '0909333444',
            'guest_email' => 'vip.client@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i'),
            'notes' => 'Mua ngay trong tuần này, đặt cọc gấp.',
        ])->assertRedirect(route('appointments.success'));

        $this->assertTrue(
            DatabaseNotification::query()
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $gmailAdmin->id)
                ->where('type', NewAppointmentLeadNotification::class)
                ->exists()
        );

        Mail::assertSent(HotLeadAlertMail::class, function (HotLeadAlertMail $mail) use ($gmailAdmin): bool {
            return $mail->hasTo($gmailAdmin->email);
        });
    }

    public function test_non_allowlisted_gmail_remains_blocked_even_when_admin_emails_configured(): void
    {
        config(['auth.admin_emails' => ['thaibanana2k2@gmail.com']]);

        $allowlistedUser = User::factory()->create(['email' => 'thaibanana2k2@gmail.com']);
        $stranger = User::factory()->create(['email' => 'stranger@gmail.com']);

        $this->assertTrue($allowlistedUser->isAdmin());
        $this->assertFalse($stranger->isAdmin());
        $this->assertFalse(User::admins()->whereKey($stranger->id)->exists());

        $this->actingAs($stranger)
            ->get(route('admin.appointments.index'))
            ->assertForbidden();
    }
}

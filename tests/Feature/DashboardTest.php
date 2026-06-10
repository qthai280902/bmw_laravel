<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $this->actingAs($user = User::factory()->create([
            'email' => 'admin@bmw.com',
        ]));

        $this->get('/dashboard')->assertOk();
    }

    public function test_non_admin_users_cannot_visit_the_dashboard(): void
    {
        $this->actingAs($user = User::factory()->create([
            'email' => 'customer@example.com',
        ]));

        $this->get('/dashboard')->assertForbidden();
    }

    public function test_dashboard_displays_crm_analytics(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@bmw.com',
        ]);
        $product = Product::factory()->create([
            'name' => 'BMW M3 Competition',
        ]);

        Appointment::factory()
            ->for($user)
            ->for($product)
            ->create([
                'appointment_date' => now(),
                'status' => AppointmentStatus::Pending,
                'type' => AppointmentType::TradeIn,
            ]);

        Appointment::factory()
            ->guest()
            ->for($product)
            ->create([
                'guest_name' => 'Guest Service Lead',
                'appointment_date' => now()->subDay(),
                'status' => AppointmentStatus::Confirmed,
                'type' => AppointmentType::Maintenance,
            ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Lead Analytics')
            ->assertSeeText('BMW M3 Competition')
            ->assertSeeText('Guest Service Lead')
            ->assertSeeText('Trade-in')
            ->assertSeeText('Service');
    }
}

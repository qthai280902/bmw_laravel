<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\OrderStatus;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6AfterSalesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $this->customer = User::factory()->create();
    }

    /** @test */
    public function user_can_book_test_drive_without_owning_the_car()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->customer)->post(route('appointments.store'), [
            'product_id' => $product->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'notes' => 'I want to try this car.',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'user_id' => $this->customer->id,
            'product_id' => $product->id,
            'type' => AppointmentType::TestDrive->value,
        ]);
    }

    /** @test */
    public function user_cannot_book_maintenance_if_they_havent_purchased_the_car()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->customer)->post(route('appointments.store'), [
            'product_id' => $product->id,
            'type' => AppointmentType::Maintenance->value,
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
        ]);

        $response->assertSessionHasErrors(['product_id']);
        $this->assertDatabaseCount('appointments', 0);
    }

    /** @test */
    public function user_can_book_maintenance_if_they_purchased_the_car()
    {
        $product = Product::factory()->create();

        // Cạo dữ liệu đơn hàng đã thanh toán
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::Paid,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->customer)->post(route('appointments.store'), [
            'product_id' => $product->id,
            'type' => AppointmentType::Maintenance->value,
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'type' => AppointmentType::Maintenance->value,
            'user_id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function vip_tier_accessor_calculates_correctly_based_on_paid_orders()
    {
        $user = User::factory()->create();

        // 3 đơn hàng đã thanh toán -> Silver
        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Paid,
        ]);

        // Kiểm tra logic với withCount (Architectural constraint)
        $queriedUser = User::withCount(['orders' => function ($q) {
            $q->where('status', OrderStatus::Paid);
        }])->find($user->id);

        $this->assertEquals('Silver', $queriedUser->vip_tier);
    }

    /** @test */
    public function admin_can_update_appointment_status()
    {
        $appointment = Appointment::create([
            'user_id' => $this->customer->id,
            'product_id' => Product::factory()->create()->id,
            'type' => AppointmentType::Viewing->value,
            'appointment_date' => now()->addDays(1),
            'status' => AppointmentStatus::Pending->value,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.appointments.update', $appointment), [
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $response->assertStatus(302);
        $this->assertEquals(AppointmentStatus::Confirmed, $appointment->fresh()->status);
    }
}

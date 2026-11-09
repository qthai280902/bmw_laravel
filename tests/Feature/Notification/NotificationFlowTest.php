<?php

namespace Tests\Feature\Notification;

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
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_creation_dispatches_event(): void
    {
        Event::fake([AppointmentCreated::class]);

        $vehicle = $this->createVehicle();

        $this->post(route('appointments.store'), [
            'guest_name' => 'Nguyen Van A',
            'guest_phone' => '0909123456',
            'guest_email' => 'customer@example.com',
            'product_id' => $vehicle->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i'),
            'notes' => 'Muon lai thu buoi sang.',
        ])->assertRedirect(route('appointments.success'));

        Event::assertDispatched(AppointmentCreated::class, function (AppointmentCreated $event) use ($vehicle): bool {
            return $event->appointment->product_id === $vehicle->id
                && $event->appointment->guest_email === 'customer@example.com';
        });
    }

    public function test_accessory_order_creation_dispatches_event(): void
    {
        Event::fake([AccessoryOrderCreated::class]);

        $accessory = $this->createAccessory();

        $this->post(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Tran Thi B',
            'customer_phone' => '0909888999',
            'customer_address' => '800 Nguyen Van Linh, Quan 7',
            'customer_email' => 'buyer@example.com',
            'quantity' => 2,
            'notes' => 'Giao gio hanh chinh.',
        ])->assertRedirect(route('accessory-orders.create', $accessory->slug));

        Event::assertDispatched(AccessoryOrderCreated::class, function (AccessoryOrderCreated $event) use ($accessory): bool {
            return $event->order->product_id === $accessory->id
                && $event->order->customer_email === 'buyer@example.com';
        });
    }

    public function test_appointment_listener_queues_mail_for_customer_and_admin(): void
    {
        Mail::fake();

        $vehicle = $this->createVehicle();
        $appointment = Appointment::create([
            'guest_name' => 'Le Hoang C',
            'guest_phone' => '0912333444',
            'guest_email' => 'lehoangc@example.com',
            'product_id' => $vehicle->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        (new SendAppointmentNotifications)->handle(new AppointmentCreated($appointment));

        Mail::assertQueued(AppointmentConfirmationMail::class, function (AppointmentConfirmationMail $mail) use ($appointment): bool {
            return $mail->hasTo('lehoangc@example.com')
                && $mail->appointment->is($appointment)
                && $mail->queue === 'notifications';
        });

        Mail::assertQueued(AppointmentAlertMail::class, function (AppointmentAlertMail $mail) use ($appointment): bool {
            return $mail->hasTo(config('mail.showroom_admin', 'advisor@bmw.com'))
                && $mail->appointment->is($appointment)
                && $mail->queue === 'notifications';
        });
    }

    public function test_appointment_listener_handles_null_customer_email_safely(): void
    {
        Mail::fake();

        $vehicle = $this->createVehicle();
        $appointment = Appointment::create([
            'guest_name' => 'Pham Van D',
            'guest_phone' => '0988777666',
            'guest_email' => null,
            'product_id' => $vehicle->id,
            'type' => AppointmentType::Quote,
            'appointment_date' => now()->addDays(3),
            'status' => AppointmentStatus::Pending,
        ]);

        (new SendAppointmentNotifications)->handle(new AppointmentCreated($appointment));

        Mail::assertNotQueued(AppointmentConfirmationMail::class);
        Mail::assertQueued(AppointmentAlertMail::class, function (AppointmentAlertMail $mail) use ($appointment): bool {
            return $mail->hasTo(config('mail.showroom_admin', 'advisor@bmw.com'))
                && $mail->appointment->is($appointment);
        });
    }

    public function test_accessory_order_listener_queues_mail_for_customer_and_admin(): void
    {
        Mail::fake();

        $accessory = $this->createAccessory();
        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Doan Minh E',
            'customer_phone' => '0933555777',
            'customer_address' => '268 Doi Can, Ba Dinh, Ha Noi',
            'customer_email' => 'doanminhe@example.com',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        (new SendAccessoryOrderNotifications)->handle(new AccessoryOrderCreated($order));

        Mail::assertQueued(AccessoryOrderConfirmationMail::class, function (AccessoryOrderConfirmationMail $mail) use ($order): bool {
            return $mail->hasTo('doanminhe@example.com')
                && $mail->order->is($order)
                && $mail->queue === 'notifications';
        });

        Mail::assertQueued(AccessoryOrderAlertMail::class, function (AccessoryOrderAlertMail $mail) use ($order): bool {
            return $mail->hasTo(config('mail.showroom_admin', 'advisor@bmw.com'))
                && $mail->order->is($order)
                && $mail->queue === 'notifications';
        });
    }

    public function test_accessory_order_listener_handles_null_customer_email_safely(): void
    {
        Mail::fake();

        $accessory = $this->createAccessory();
        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Vu Thi F',
            'customer_phone' => '0977111222',
            'customer_address' => '100 Tran Hung Dao, Quan 1',
            'customer_email' => null,
            'quantity' => 3,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        (new SendAccessoryOrderNotifications)->handle(new AccessoryOrderCreated($order));

        Mail::assertNotQueued(AccessoryOrderConfirmationMail::class);
        Mail::assertQueued(AccessoryOrderAlertMail::class, function (AccessoryOrderAlertMail $mail) use ($order): bool {
            return $mail->hasTo(config('mail.showroom_admin', 'advisor@bmw.com'))
                && $mail->order->is($order);
        });
    }

    private function createVehicle(): Product
    {
        $category = Category::create([
            'name' => 'BMW Sedan',
            'slug' => 'bmw-sedan',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'deposit_amount' => 50000000,
            'stock' => 5,
            'specifications' => ['Engine' => '2.0L TwinPower Turbo', 'Horsepower' => '258 HP'],
            'description' => 'Mẫu sedan thể thao BMW 330i M Sport.',
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    private function createAccessory(): Product
    {
        $category = Category::create([
            'name' => 'Phụ kiện BMW',
            'slug' => 'phu-kien-bmw',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Thảm sàn BMW M Performance',
            'slug' => 'tham-san-bmw-m-performance',
            'type' => VehicleType::ACCESSORY,
            'price' => 8500000,
            'deposit_amount' => 0,
            'stock' => 20,
            'specifications' => ['Compatibility' => 'BMW 3 Series G20'],
            'description' => 'Thảm sàn chính hãng BMW M Performance.',
            'is_featured' => false,
            'is_active' => true,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Mail\Admin\ShowroomDailySummaryMail;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Services\Admin\AdminNotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Mail;

class SendShowroomDailySummaryCommand extends Command implements Isolatable
{
    protected $signature = 'showroom:daily-summary';

    protected $description = 'Gửi báo cáo tổng kết vận hành cuối ngày (18:00) tới hộp thư quản trị BMW Showroom';

    public function handle(): int
    {
        $startOfDay = now()->copy()->startOfDay();
        $endOfDay = now()->copy()->endOfDay();

        $todayAppointments = Appointment::query()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get(['id', 'type', 'status', 'lead_priority']);

        $serviceTypes = [
            AppointmentType::Maintenance->value,
            AppointmentType::Detailing->value,
            AppointmentType::CarWash->value,
        ];

        $todayOrders = AccessoryOrder::query()
            ->with('product:id,price')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get(['id', 'product_id', 'quantity']);

        $summary = [
            'date_label' => now()->format('d/m/Y'),
            'total_appointments' => $todayAppointments->count(),
            'hot_leads' => $todayAppointments->where('lead_priority', Appointment::LEAD_PRIORITY_HOT)->count(),
            'service_bookings' => $todayAppointments->filter(
                static fn (Appointment $item): bool => in_array(
                    $item->type instanceof AppointmentType ? $item->type->value : (string) $item->type,
                    $serviceTypes,
                    true
                )
            )->count(),
            'confirmed_or_completed' => $todayAppointments->filter(
                static fn (Appointment $item): bool => in_array(
                    $item->status instanceof AppointmentStatus ? $item->status->value : (string) $item->status,
                    [AppointmentStatus::Confirmed->value, AppointmentStatus::Completed->value],
                    true
                )
            )->count(),
            'accessory_orders_count' => $todayOrders->count(),
            'accessory_orders_value' => (int) $todayOrders->sum(
                static fn (AccessoryOrder $order): int => $order->total_amount
            ),
        ];

        $recipients = AdminNotificationDispatcher::showroomAdminEmails();

        Mail::to($recipients)->send(new ShowroomDailySummaryMail($summary));

        $this->info('Đã gửi báo cáo tổng kết ngày '.$summary['date_label'].' tới '.count($recipients).' địa chỉ quản trị.');

        return self::SUCCESS;
    }
}

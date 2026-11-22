<?php

namespace App\Http\Controllers\Admin;

use App\Collections\AccessoryOrderCollection;
use App\Collections\AppointmentCollection;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\AccessoryOrder;
use App\Models\AccountingDailyClosing;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingController extends Controller
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function index(Request $request): View
    {
        $period = (string) $request->query('period', 'this_month');
        if (! in_array($period, ['today', '7d', 'this_month', 'this_quarter', 'custom'], true)) {
            $period = 'this_month';
        }

        $module = (string) $request->query('module', 'all');
        if (! in_array($module, ['all', 'accessories', 'services', 'vehicles'], true)) {
            $module = 'all';
        }

        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'pending', 'confirmed', 'completed', 'cancelled'], true)) {
            $status = 'all';
        }

        $dateFromInput = trim((string) $request->query('date_from', ''));
        $dateToInput = trim((string) $request->query('date_to', ''));

        [$startVn, $endVn] = $this->resolvePeriodRange($period, $dateFromInput, $dateToInput);
        $accountingData = $this->collectAccountingData($startVn, $endVn, $module, $status);
        $vouchers = $accountingData['vouchers'];
        $ordersCollection = $accountingData['orders'];
        $appointmentsCollection = $accountingData['appointments'];

        // 5 Financial KPIs computed via custom Eloquent Collections
        $completedAccessoryRevenue = $ordersCollection->completedRevenue();
        $processingAccessoryValue = $ordersCollection->processingValue();
        $vehicleCommittedDeposit = $appointmentsCollection->committedDepositValue();
        $vehicleReferenceValue = $appointmentsCollection->referenceVehicleValue();
        $cancelledLostValue = $ordersCollection->cancelledValue() + $appointmentsCollection->cancelledDepositValue();
        $completedVouchersCount = $ordersCollection->statusCounts()['completed']
            + $appointmentsCollection->statusCounts()['completed'];

        $totalVouchersCount = count($vouchers);
        $completionRate = $totalVouchersCount > 0
            ? round(($completedVouchersCount / $totalVouchersCount) * 100, 1)
            : 0.0;

        // Group vouchers by date (YYYY-MM-DD in Asia/Ho_Chi_Minh)
        $groupedByDate = [];
        foreach ($vouchers as $v) {
            $d = $v['date_key'];
            if (! isset($groupedByDate[$d])) {
                $groupedByDate[$d] = [
                    'date' => $d,
                    'date_label' => $v['date_label'],
                    'short_label' => $v['short_date_label'],
                    'vehicles_count' => 0,
                    'services_count' => 0,
                    'orders_count' => 0,
                    'total_vouchers' => 0,
                    'completed_revenue' => 0,
                    'committed_value' => 0,
                    'cancelled_value' => 0,
                ];
            }

            $groupedByDate[$d]['total_vouchers']++;

            if ($v['module_key'] === 'accessories') {
                $groupedByDate[$d]['orders_count']++;
                if ($v['status'] === 'completed') {
                    $groupedByDate[$d]['completed_revenue'] += $v['amount'];
                } elseif (in_array($v['status'], ['pending', 'confirmed'], true)) {
                    $groupedByDate[$d]['committed_value'] += $v['amount'];
                } elseif ($v['status'] === 'cancelled') {
                    $groupedByDate[$d]['cancelled_value'] += $v['amount'];
                }
            } elseif ($v['module_key'] === 'services') {
                $groupedByDate[$d]['services_count']++;
            } else {
                $groupedByDate[$d]['vehicles_count']++;
                if (in_array($v['status'], ['confirmed', 'completed'], true)) {
                    $groupedByDate[$d]['committed_value'] += $v['deposit_amount'];
                } elseif ($v['status'] === 'cancelled') {
                    $groupedByDate[$d]['cancelled_value'] += $v['deposit_amount'];
                }
            }
        }

        // Load daily closings for dates in range
        $closingsByDate = AccountingDailyClosing::query()
            ->with('closedBy:id,name,email')
            ->whereBetween('closing_date', [$startVn->toDateString(), $endVn->toDateString()])
            ->get()
            ->keyBy(static fn (AccountingDailyClosing $c): string => $c->closing_date->format('Y-m-d'));

        // Include dates that have a closing snapshot even if 0 vouchers match filter
        foreach ($closingsByDate as $closingDateKey => $closingModel) {
            if (! isset($groupedByDate[$closingDateKey])) {
                $parsedD = CarbonImmutable::parse($closingDateKey, self::TIMEZONE);
                $groupedByDate[$closingDateKey] = [
                    'date' => $closingDateKey,
                    'date_label' => $parsedD->format('d/m/Y'),
                    'short_label' => $parsedD->format('d/m'),
                    'vehicles_count' => $closingModel->appointments_count,
                    'services_count' => $closingModel->services_count,
                    'orders_count' => $closingModel->orders_count,
                    'total_vouchers' => $closingModel->appointments_count + $closingModel->services_count + $closingModel->orders_count,
                    'completed_revenue' => $closingModel->completed_revenue,
                    'committed_value' => $closingModel->committed_value,
                    'cancelled_value' => $closingModel->cancelled_value,
                ];
            }
        }

        krsort($groupedByDate);

        $dailyPacks = [];
        foreach ($groupedByDate as $dateKey => $pack) {
            $pack['closing'] = $closingsByDate->get($dateKey);
            $dailyPacks[] = $pack;
        }

        // Chart series (chronological up to 14 points)
        $chartPoints = array_reverse(array_slice($dailyPacks, 0, 14));
        if ($chartPoints === []) {
            $chartPoints[] = [
                'date' => $endVn->toDateString(),
                'date_label' => $endVn->format('d/m/Y'),
                'short_label' => $endVn->format('d/m'),
                'vehicles_count' => 0,
                'services_count' => 0,
                'orders_count' => 0,
                'total_vouchers' => 0,
                'completed_revenue' => 0,
                'committed_value' => 0,
                'cancelled_value' => 0,
            ];
        }

        return view('admin.accounting.index', [
            'period' => $period,
            'module' => $module,
            'status' => $status,
            'dateFrom' => $dateFromInput !== '' ? $dateFromInput : $startVn->toDateString(),
            'dateTo' => $dateToInput !== '' ? $dateToInput : $endVn->toDateString(),
            'periodLabel' => $startVn->format('d/m/Y').' — '.$endVn->format('d/m/Y'),
            'kpis' => [
                'completed_accessory_revenue' => $completedAccessoryRevenue,
                'processing_accessory_value' => $processingAccessoryValue,
                'vehicle_committed_deposit' => $vehicleCommittedDeposit,
                'vehicle_reference_value' => $vehicleReferenceValue,
                'cancelled_lost_value' => $cancelledLostValue,
                'completion_rate' => $completionRate,
                'completed_vouchers_count' => $completedVouchersCount,
                'total_vouchers_count' => $totalVouchersCount,
            ],
            'chartPoints' => $chartPoints,
            'dailyPacks' => $dailyPacks,
            'recentVouchers' => array_slice($vouchers, 0, 25),
        ]);
    }

    public function showDay(string $date): View
    {
        try {
            $dayVn = CarbonImmutable::createFromFormat('Y-m-d', $date, self::TIMEZONE);
        } catch (\Throwable) {
            abort(404);
        }

        if (! $dayVn || $dayVn->format('Y-m-d') !== $date) {
            abort(404);
        }

        $startVn = $dayVn->startOfDay();
        $endVn = $dayVn->endOfDay();

        $vouchers = $this->collectVouchers($startVn, $endVn, 'all', 'all');
        $summary = $this->summarizeDayVouchers($vouchers);

        $closing = AccountingDailyClosing::query()
            ->with('closedBy:id,name,email')
            ->whereDate('closing_date', $date)
            ->first();

        return view('admin.accounting.day', [
            'date' => $date,
            'dateLabel' => $dayVn->format('d/m/Y'),
            'vouchers' => $vouchers,
            'summary' => $summary,
            'closing' => $closing,
        ]);
    }

    public function closeDay(Request $request, string $date): RedirectResponse
    {
        try {
            $dayVn = CarbonImmutable::createFromFormat('Y-m-d', $date, self::TIMEZONE);
        } catch (\Throwable) {
            abort(404);
        }

        if (! $dayVn || $dayVn->format('Y-m-d') !== $date) {
            abort(404);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $vouchers = $this->collectVouchers($dayVn->startOfDay(), $dayVn->endOfDay(), 'all', 'all');
        $summary = $this->summarizeDayVouchers($vouchers);

        AccountingDailyClosing::query()->updateOrCreate(
            ['closing_date' => $date],
            [
                'closed_by' => $request->user()?->id,
                'closed_at' => now(),
                'appointments_count' => $summary['vehicles_count'],
                'services_count' => $summary['services_count'],
                'orders_count' => $summary['orders_count'],
                'completed_revenue' => $summary['completed_revenue'],
                'committed_value' => $summary['committed_value'],
                'cancelled_value' => $summary['cancelled_value'],
                'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
            ]
        );

        return redirect()
            ->route('admin.accounting.day', ['date' => $date])
            ->with('status', 'Đã chốt sổ kế toán ngày '.$dayVn->format('d/m/Y').' thành công.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $singleDate = trim((string) $request->query('date', ''));

        if ($singleDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $singleDate)) {
            $dayVn = CarbonImmutable::parse($singleDate, self::TIMEZONE);
            $startVn = $dayVn->startOfDay();
            $endVn = $dayVn->endOfDay();
            $module = 'all';
            $status = 'all';
            $filename = "so-ke-toan-bmw-ngay-{$singleDate}.csv";
        } else {
            $period = (string) $request->query('period', 'this_month');
            $module = (string) $request->query('module', 'all');
            $status = (string) $request->query('status', 'all');
            $dateFrom = trim((string) $request->query('date_from', ''));
            $dateTo = trim((string) $request->query('date_to', ''));
            [$startVn, $endVn] = $this->resolvePeriodRange($period, $dateFrom, $dateTo);
            $filename = 'so-ke-toan-bmw-'.$startVn->format('Ymd').'-'.$endVn->format('Ymd').'.csv';
        }

        $vouchers = $this->collectVouchers($startVn, $endVn, $module, $status);

        return response()->streamDownload(function () use ($vouchers): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel Vietnamese compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Mã Chứng Từ (CT)',
                'Mã Tham Chiếu (KH)',
                'Mã KH Nội Bộ',
                'Ngày Ghi Nhận',
                'Giờ',
                'Phân Hệ',
                'Khách Hàng',
                'Số Điện Thoại',
                'Sản Phẩm / Hạng Mục',
                'Số Lượng',
                'Đơn Giá / Tiền Cọc (VNĐ)',
                'Thành Tiền Thực Thu (VNĐ)',
                'Giá Trị Niêm Yết Tham Chiếu (VNĐ)',
                'Sales Phụ Trách',
                'Trạng Thái',
            ]);

            foreach ($vouchers as $v) {
                fputcsv($handle, [
                    $v['voucher_code'],
                    $v['reference_code'],
                    $v['customer_internal_code'],
                    $v['date_label'],
                    $v['time_label'],
                    $v['module_label'],
                    $v['customer_name'],
                    $v['customer_phone'],
                    $v['item_name'],
                    $v['quantity'],
                    $v['unit_or_deposit_amount'],
                    $v['realized_revenue'],
                    $v['reference_price'],
                    $v['sales_name'],
                    $v['status_label'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolvePeriodRange(string $period, string $dateFrom, string $dateTo): array
    {
        $nowVn = CarbonImmutable::now(self::TIMEZONE);

        if ($period === 'today') {
            return [$nowVn->startOfDay(), $nowVn->endOfDay()];
        }

        if ($period === '7d') {
            return [$nowVn->subDays(6)->startOfDay(), $nowVn->endOfDay()];
        }

        if ($period === 'this_quarter') {
            return [$nowVn->firstOfQuarter()->startOfDay(), $nowVn->lastOfQuarter()->endOfDay()];
        }

        if ($period === 'custom' || $dateFrom !== '' || $dateTo !== '') {
            try {
                $start = $dateFrom !== ''
                    ? CarbonImmutable::parse($dateFrom, self::TIMEZONE)->startOfDay()
                    : $nowVn->startOfMonth()->startOfDay();
            } catch (\Throwable) {
                $start = $nowVn->startOfMonth()->startOfDay();
            }

            try {
                $end = $dateTo !== ''
                    ? CarbonImmutable::parse($dateTo, self::TIMEZONE)->endOfDay()
                    : $nowVn->endOfDay();
            } catch (\Throwable) {
                $end = $nowVn->endOfDay();
            }

            if ($end->lessThan($start)) {
                [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
            }

            return [$start, $end];
        }

        return [$nowVn->startOfMonth()->startOfDay(), $nowVn->endOfMonth()->endOfDay()];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectVouchers(
        CarbonImmutable $startVn,
        CarbonImmutable $endVn,
        string $module,
        string $status
    ): array {
        return $this->collectAccountingData($startVn, $endVn, $module, $status)['vouchers'];
    }

    /**
     * @return array{
     *     vouchers: list<array<string, mixed>>,
     *     orders: AccessoryOrderCollection,
     *     appointments: AppointmentCollection
     * }
     */
    private function collectAccountingData(
        CarbonImmutable $startVn,
        CarbonImmutable $endVn,
        string $module,
        string $status
    ): array {
        $appTz = (string) config('app.timezone', 'UTC');
        $startDb = $startVn->setTimezone($appTz)->toDateTimeString();
        $endDb = $endVn->setTimezone($appTz)->toDateTimeString();

        $rows = [];
        $ordersCollection = new AccessoryOrderCollection;
        $appointmentsCollection = new AppointmentCollection;

        if (in_array($module, ['all', 'accessories'], true)) {
            $ordersQuery = AccessoryOrder::query()
                ->with(['product:id,name,price', 'assignedSales:id,name'])
                ->whereBetween('created_at', [$startDb, $endDb])
                ->when($status !== 'all', static fn ($q) => $q->where('status', $status))
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            /** @var AccessoryOrderCollection $ordersCollection */
            $ordersCollection = $ordersQuery->get();

            foreach ($ordersCollection as $order) {
                $createdVn = ($order->created_at ?? now())->copy()->timezone(self::TIMEZONE);
                $unitPrice = (int) ($order->product?->price ?? 0);
                $qty = max(1, (int) $order->quantity);
                $totalAmount = $unitPrice * $qty;

                $rows[] = [
                    'sort_ts' => $createdVn->getTimestamp(),
                    'date_key' => $createdVn->format('Y-m-d'),
                    'date_label' => $createdVn->format('d/m/Y'),
                    'short_date_label' => $createdVn->format('d/m'),
                    'time_label' => $createdVn->format('H:i'),
                    'voucher_code' => $order->internalVoucherCode(),
                    'reference_code' => $order->publicReferenceCode(),
                    'customer_internal_code' => $order->internalCustomerCode(),
                    'module_key' => 'accessories',
                    'module_label' => 'Đơn phụ kiện',
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'item_name' => $order->product?->name ?? 'Phụ kiện chính hãng BMW',
                    'quantity' => $qty,
                    'amount' => $totalAmount,
                    'deposit_amount' => 0,
                    'unit_or_deposit_amount' => $unitPrice,
                    'realized_revenue' => $order->status === AccessoryOrder::STATUS_COMPLETED ? $totalAmount : 0,
                    'reference_price' => $totalAmount,
                    'sales_name' => $order->assignedSales?->exists ? $order->assignedSales->name : 'Chưa gán',
                    'status' => (string) $order->status,
                    'status_label' => $order->statusLabel(),
                    'is_archived' => $order->isArchived(),
                    'cancellation_label' => $order->cancellationAttributionLabel(),
                    'completion_outcome' => null,
                    'completion_outcome_label' => null,
                    'care_voucher_code' => null,
                    'care_voucher_expires_label' => null,
                ];
            }
        }

        if (in_array($module, ['all', 'services', 'vehicles'], true)) {
            $appointmentsQuery = Appointment::query()
                ->with(['user:id,name,email', 'product:id,name,price,deposit_amount', 'assignedSales:id,name'])
                ->whereBetween('created_at', [$startDb, $endDb])
                ->when($status !== 'all', static fn ($q) => $q->where('status', $status))
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            /** @var AppointmentCollection $rawAppointments */
            $rawAppointments = $appointmentsQuery->get();
            $appointmentsCollection = match ($module) {
                'services' => $rawAppointments->serviceBookings(),
                'vehicles' => $rawAppointments->vehicleBookings(),
                default => $rawAppointments,
            };

            foreach ($appointmentsCollection as $appointment) {
                $isService = $appointment->isServiceBooking();
                $createdVn = ($appointment->created_at ?? now())->copy()->timezone(self::TIMEZONE);
                $statusVal = $appointment->status instanceof AppointmentStatus
                    ? $appointment->status->value
                    : (string) $appointment->status;
                $refPrice = (int) ($appointment->product?->price ?? 0);
                $depositAmount = AppointmentCollection::resolveDepositAmount($appointment);

                $itemName = $isService
                    ? ($appointment->servicePackageLabel() ?? $appointment->typeLabel())
                    : (($appointment->product?->name ?? 'Tư vấn dòng xe BMW').' ('.$appointment->typeLabel().')');

                $rows[] = [
                    'sort_ts' => $createdVn->getTimestamp(),
                    'date_key' => $createdVn->format('Y-m-d'),
                    'date_label' => $createdVn->format('d/m/Y'),
                    'short_date_label' => $createdVn->format('d/m'),
                    'time_label' => $createdVn->format('H:i'),
                    'voucher_code' => $appointment->internalVoucherCode(),
                    'reference_code' => $appointment->publicReferenceCode(),
                    'customer_internal_code' => $appointment->internalCustomerCode(),
                    'module_key' => $isService ? 'services' : 'vehicles',
                    'module_label' => $isService ? 'Dịch vụ xưởng' : 'Lịch hẹn xe',
                    'customer_name' => $appointment->customer_name,
                    'customer_phone' => $appointment->customer_phone,
                    'item_name' => $itemName,
                    'quantity' => 1,
                    'amount' => $depositAmount,
                    'deposit_amount' => $depositAmount,
                    'unit_or_deposit_amount' => $depositAmount,
                    'realized_revenue' => 0,
                    'reference_price' => $refPrice,
                    'sales_name' => $appointment->assignedSales?->exists ? $appointment->assignedSales->name : 'Chưa gán',
                    'status' => $statusVal,
                    'status_label' => $appointment->statusLabel(),
                    'is_archived' => $appointment->isArchived(),
                    'cancellation_label' => $appointment->cancellationAttributionLabel(),
                    'completion_outcome' => $appointment->completionOutcome(),
                    'completion_outcome_label' => $appointment->completionOutcomeLabel(),
                    'care_voucher_code' => $appointment->careVoucherCode(),
                    'care_voucher_expires_label' => $appointment->careVoucherExpiresLabel(),
                ];
            }
        }

        usort($rows, static fn (array $a, array $b): int => $b['sort_ts'] <=> $a['sort_ts']);

        return [
            'vouchers' => $rows,
            'orders' => $ordersCollection,
            'appointments' => $appointmentsCollection,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $vouchers
     * @return array{
     *     vehicles_count: int,
     *     services_count: int,
     *     orders_count: int,
     *     total_vouchers: int,
     *     completed_revenue: int,
     *     committed_value: int,
     *     cancelled_value: int
     * }
     */
    private function summarizeDayVouchers(array $vouchers): array
    {
        $vehiclesCount = 0;
        $servicesCount = 0;
        $ordersCount = 0;
        $completedRevenue = 0;
        $committedValue = 0;
        $cancelledValue = 0;

        foreach ($vouchers as $v) {
            if ($v['module_key'] === 'accessories') {
                $ordersCount++;
                if ($v['status'] === 'completed') {
                    $completedRevenue += $v['amount'];
                } elseif (in_array($v['status'], ['pending', 'confirmed'], true)) {
                    $committedValue += $v['amount'];
                } elseif ($v['status'] === 'cancelled') {
                    $cancelledValue += $v['amount'];
                }
            } elseif ($v['module_key'] === 'services') {
                $servicesCount++;
            } else {
                $vehiclesCount++;
                if (in_array($v['status'], ['confirmed', 'completed'], true)) {
                    $committedValue += $v['deposit_amount'];
                } elseif ($v['status'] === 'cancelled') {
                    $cancelledValue += $v['deposit_amount'];
                }
            }
        }

        return [
            'vehicles_count' => $vehiclesCount,
            'services_count' => $servicesCount,
            'orders_count' => $ordersCount,
            'total_vouchers' => count($vouchers),
            'completed_revenue' => $completedRevenue,
            'committed_value' => $committedValue,
            'cancelled_value' => $cancelledValue,
        ];
    }
}

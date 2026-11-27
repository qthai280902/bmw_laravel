<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\CustomerInstallmentPlan;
use App\Services\Finance\InstallmentScheduleCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerPortalLookupController extends Controller
{
    public function index(): View
    {
        return view('portal.lookup', [
            'searched' => false,
            'found' => false,
            'searchQuery' => '',
            'profile' => null,
            'installmentEntries' => collect(),
            'vehicleAppointments' => collect(),
            'serviceBookings' => collect(),
            'accessoryOrders' => collect(),
        ]);
    }

    public function search(Request $request, InstallmentScheduleCalculator $calculator): View
    {
        if (! $request->filled('query')) {
            $fallback = $request->input('identifier') ?? $request->input('q');
            if (is_string($fallback) && trim($fallback) !== '') {
                $request->merge(['query' => $fallback]);
            }
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:190'],
        ], [
            'query.required' => 'Vui lòng nhập Số điện thoại hoặc địa chỉ Email đã đăng ký tại BMW Showroom.',
        ]);

        $rawQuery = trim((string) $validated['query']);
        $isEmail = filter_var($rawQuery, FILTER_VALIDATE_EMAIL) !== false;

        $seedEmails = [];
        $seedPhones = [];

        if ($isEmail) {
            $seedEmails[] = mb_strtolower($rawQuery);
        } else {
            $normalizedPhone = CustomerInstallmentPlan::normalizePhoneDigits($rawQuery);
            if ($normalizedPhone === '' || strlen($normalizedPhone) < 8 || strlen($normalizedPhone) > 15) {
                throw ValidationException::withMessages([
                    'query' => 'Vui lòng nhập địa chỉ Email hợp lệ hoặc Số điện thoại từ 8 đến 15 chữ số.',
                ]);
            }
            $seedPhones[] = $normalizedPhone;
        }

        $bundle = $this->resolveCrossLinkedCustomerRecords($seedEmails, $seedPhones, $calculator);

        return view('portal.lookup', [
            'searched' => true,
            'found' => $bundle['found'],
            'searchQuery' => $rawQuery,
            'profile' => $bundle['profile'],
            'installmentEntries' => $bundle['installmentEntries'],
            'vehicleAppointments' => $bundle['vehicleAppointments'],
            'serviceBookings' => $bundle['serviceBookings'],
            'accessoryOrders' => $bundle['accessoryOrders'],
        ]);
    }

    /**
     * Cross-link Phone <-> Email across Appointments, Accessory Orders & Installment Plans without N+1 or internal leaks.
     *
     * @param  list<string>  $seedEmails
     * @param  list<string>  $seedPhones
     * @return array{
     *     found: bool,
     *     profile: array{name: string, phone: string, email: string, advisor_name: string}|null,
     *     installmentEntries: Collection<int, array{plan: CustomerInstallmentPlan, calc: array<string, mixed>}>,
     *     vehicleAppointments: Collection<int, Appointment>,
     *     serviceBookings: Collection<int, Appointment>,
     *     accessoryOrders: Collection<int, AccessoryOrder>
     * }
     */
    private function resolveCrossLinkedCustomerRecords(
        array $seedEmails,
        array $seedPhones,
        InstallmentScheduleCalculator $calculator,
    ): array {
        $allAppointments = Appointment::query()
            ->with([
                'user:id,name,email,phone',
                'product:id,name,slug,price',
                'assignedSales:id,name,role',
            ])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get();

        $allOrders = AccessoryOrder::query()
            ->with([
                'product:id,name,price',
                'assignedSales:id,name,role',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $allPlans = CustomerInstallmentPlan::query()
            ->with([
                'product:id,name,price',
                'appointment:id,customer_order_code',
            ])
            ->orderByRaw("CASE WHEN status = 'overdue' THEN 0 WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderBy('next_due_date')
            ->orderByDesc('id')
            ->get();

        $knownEmails = array_fill_keys($seedEmails, true);
        $knownPhones = array_fill_keys($seedPhones, true);

        // Two-pass transitive closure so Email lookup discovers Phone records and vice versa
        for ($pass = 0; $pass < 2; $pass++) {
            foreach ($allAppointments as $appointment) {
                $email = mb_strtolower(trim((string) ($appointment->guest_email ?? $appointment->user?->email ?? '')));
                $phone = CustomerInstallmentPlan::normalizePhoneDigits($appointment->guest_phone ?: $appointment->user?->phone);

                if (($email !== '' && isset($knownEmails[$email])) || ($phone !== '' && isset($knownPhones[$phone]))) {
                    if ($email !== '') {
                        $knownEmails[$email] = true;
                    }
                    if ($phone !== '') {
                        $knownPhones[$phone] = true;
                    }
                }
            }

            foreach ($allOrders as $order) {
                $email = mb_strtolower(trim((string) ($order->customer_email ?? '')));
                $phone = CustomerInstallmentPlan::normalizePhoneDigits($order->customer_phone);

                if (($email !== '' && isset($knownEmails[$email])) || ($phone !== '' && isset($knownPhones[$phone]))) {
                    if ($email !== '') {
                        $knownEmails[$email] = true;
                    }
                    if ($phone !== '') {
                        $knownPhones[$phone] = true;
                    }
                }
            }

            foreach ($allPlans as $plan) {
                $email = mb_strtolower(trim((string) ($plan->customer_email ?? '')));
                $phone = CustomerInstallmentPlan::normalizePhoneDigits($plan->customer_phone);

                if (($email !== '' && isset($knownEmails[$email])) || ($phone !== '' && isset($knownPhones[$phone]))) {
                    if ($email !== '') {
                        $knownEmails[$email] = true;
                    }
                    if ($phone !== '') {
                        $knownPhones[$phone] = true;
                    }
                }
            }
        }

        $matchedAppointments = $allAppointments
            ->filter(static function (Appointment $appointment) use ($knownEmails, $knownPhones): bool {
                $email = mb_strtolower(trim((string) ($appointment->guest_email ?? $appointment->user?->email ?? '')));
                $phone = CustomerInstallmentPlan::normalizePhoneDigits($appointment->guest_phone ?: $appointment->user?->phone);

                return ($email !== '' && isset($knownEmails[$email]))
                    || ($phone !== '' && isset($knownPhones[$phone]));
            })
            ->values();

        $matchedOrders = $allOrders
            ->filter(static function (AccessoryOrder $order) use ($knownEmails, $knownPhones): bool {
                $email = mb_strtolower(trim((string) ($order->customer_email ?? '')));
                $phone = CustomerInstallmentPlan::normalizePhoneDigits($order->customer_phone);

                return ($email !== '' && isset($knownEmails[$email]))
                    || ($phone !== '' && isset($knownPhones[$phone]));
            })
            ->values();

        $matchedPlans = $allPlans
            ->filter(static function (CustomerInstallmentPlan $plan) use ($knownEmails, $knownPhones): bool {
                if ($plan->status === CustomerInstallmentPlan::STATUS_DRAFT) {
                    return false;
                }

                $email = mb_strtolower(trim((string) ($plan->customer_email ?? '')));
                $phone = CustomerInstallmentPlan::normalizePhoneDigits($plan->customer_phone);

                return ($email !== '' && isset($knownEmails[$email]))
                    || ($phone !== '' && isset($knownPhones[$phone]));
            })
            ->values();

        if ($matchedAppointments->isEmpty() && $matchedOrders->isEmpty() && $matchedPlans->isEmpty()) {
            return [
                'found' => false,
                'profile' => null,
                'installmentEntries' => collect(),
                'vehicleAppointments' => collect(),
                'serviceBookings' => collect(),
                'accessoryOrders' => collect(),
            ];
        }

        $firstAppointment = $matchedAppointments->first();
        $firstOrder = $matchedOrders->first();
        $firstPlan = $matchedPlans->first();

        $resolvedName = trim((string) (
            $firstAppointment?->guest_name
            ?? $firstAppointment?->user?->name
            ?? $firstPlan?->customer_name
            ?? $firstOrder?->customer_name
            ?? 'Quý khách BMW'
        ));

        $resolvedPhone = trim((string) (
            $firstAppointment?->guest_phone
            ?? $firstPlan?->customer_phone
            ?? $firstOrder?->customer_phone
            ?? 'Chưa cập nhật'
        ));

        $resolvedEmail = trim((string) (
            $firstAppointment?->guest_email
            ?? $firstAppointment?->user?->email
            ?? $firstPlan?->customer_email
            ?? $firstOrder?->customer_email
            ?? 'Chưa cập nhật'
        ));

        $assignedAdvisor = $matchedAppointments
            ->first(static fn (Appointment $a): bool => $a->assigned_to !== null && $a->assignedSales?->exists)?->assignedSales?->name
            ?? $matchedOrders
                ->first(static fn (AccessoryOrder $o): bool => $o->assigned_to !== null && $o->assignedSales?->exists)?->assignedSales?->name
            ?? 'Đội ngũ Tư vấn BMW Showroom';

        $installmentEntries = $matchedPlans->map(static fn (CustomerInstallmentPlan $plan): array => [
            'plan' => $plan,
            'calc' => $calculator->calculate($plan),
        ])->values();

        return [
            'found' => true,
            'profile' => [
                'name' => $resolvedName,
                'phone' => $resolvedPhone,
                'email' => $resolvedEmail,
                'advisor_name' => trim((string) $assignedAdvisor),
            ],
            'installmentEntries' => $installmentEntries,
            'vehicleAppointments' => $matchedAppointments
                ->filter(static fn (Appointment $a): bool => ! $a->isServiceBooking())
                ->values(),
            'serviceBookings' => $matchedAppointments
                ->filter(static fn (Appointment $a): bool => $a->isServiceBooking())
                ->values(),
            'accessoryOrders' => $matchedOrders,
        ];
    }
}

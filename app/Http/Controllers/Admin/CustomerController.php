<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Events\PromotionCampaignDispatched;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $rawStatus = trim((string) $request->query('status', ''));
        $validStatuses = array_column(AppointmentStatus::cases(), 'value');
        $statusFilter = in_array($rawStatus, $validStatuses, true) ? $rawStatus : '';
        $productIdFilter = $request->filled('product_id') && is_numeric($request->query('product_id'))
            ? (int) $request->query('product_id')
            : null;

        $customers = Appointment::select(
            DB::raw('COALESCE(appointments.guest_email, users.email) as email'),
            DB::raw('MAX(COALESCE(appointments.guest_name, users.name)) as name'),
            DB::raw("MAX(COALESCE(appointments.guest_phone, 'N/A')) as phone"),
            DB::raw('COUNT(*) as interactions_count'),
            DB::raw('MAX(appointments.appointment_date) as last_interaction')
        )
            ->leftJoin('users', 'appointments.user_id', '=', 'users.id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('appointments.guest_name', 'like', "%{$search}%")
                        ->orWhere('appointments.guest_email', 'like', "%{$search}%")
                        ->orWhere('appointments.guest_phone', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($statusFilter !== '', fn ($query) => $query->where('appointments.status', $statusFilter))
            ->when($productIdFilter !== null, fn ($query) => $query->where('appointments.product_id', $productIdFilter))
            ->groupBy(DB::raw('COALESCE(appointments.guest_email, users.email)'))
            ->orderBy('last_interaction', 'desc')
            ->paginate(15)
            ->withQueryString();

        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $statuses = AppointmentStatus::cases();
        $promotionAudienceTuples = Auth::user()?->canAccessContent()
            ? $this->buildPromotionAudienceTuples()
            : [];

        return view('admin.customers.index', compact(
            'customers',
            'products',
            'statuses',
            'statusFilter',
            'productIdFilter',
            'promotionAudienceTuples',
        ));
    }

    public function sendPromotions(Request $request): RedirectResponse
    {
        if ($request->has('product_id') && trim((string) $request->input('product_id')) === '') {
            $request->merge(['product_id' => null]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:4000'],
            'audience' => ['required', 'string', Rule::in(['all', 'completed', 'by_product'])],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
        ]);

        if ($validated['audience'] === 'by_product' && empty($validated['product_id'])) {
            return back()
                ->withInput()
                ->withErrors(['product_id' => 'Vui lòng chọn mẫu xe cụ thể để lọc khách hàng mục tiêu.']);
        }

        $productId = ! empty($validated['product_id']) ? (int) $validated['product_id'] : null;
        $onlyCompleted = $validated['audience'] === 'completed' || $request->boolean('only_completed');

        $recipients = $this->resolvePromotionRecipients($productId, $onlyCompleted);

        if ($recipients === []) {
            return back()
                ->withInput()
                ->with('error', 'Không tìm thấy khách hàng nào có email hợp lệ khớp điều kiện lọc.');
        }

        event(new PromotionCampaignDispatched(
            campaignId: (string) Str::uuid(),
            offerTitle: trim(strip_tags($validated['title'])),
            offerContent: trim($validated['content']),
            recipients: $recipients,
        ));

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Đã phát chiến dịch ưu đãi tới '.count($recipients).' khách hàng mục tiêu.');
    }

    /**
     * Resolve deduplicated promotion recipients, prioritizing completed appointments
     * and latest interaction when selecting the personalized vehicle model.
     *
     * @return list<array{email: string, name: string, vehicle_name: string}>
     */
    private function resolvePromotionRecipients(?int $productId, bool $onlyCompleted): array
    {
        return Appointment::query()
            ->with(['user', 'product'])
            ->when($onlyCompleted, fn ($query) => $query->where('status', AppointmentStatus::Completed->value))
            ->when($productId !== null, fn ($query) => $query->where('product_id', $productId))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [AppointmentStatus::Completed->value])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Appointment $appointment): ?array {
                $email = mb_strtolower(trim((string) ($appointment->guest_email ?? $appointment->user?->email ?? '')));

                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                return [
                    'email' => $email,
                    'name' => trim((string) ($appointment->user?->name ?? $appointment->guest_name ?? 'Quý khách')),
                    'vehicle_name' => trim((string) (
                        $appointment->product?->name
                        ?? ($appointment->meta_data['customer_car_model'] ?? 'Các dòng xe BMW')
                    )),
                    '_priority' => $appointment->status === AppointmentStatus::Completed ? 0 : 1,
                    '_date' => $appointment->appointment_date?->getTimestamp() ?? 0,
                    '_id' => (int) $appointment->id,
                ];
            })
            ->filter()
            ->sort(static function (array $a, array $b): int {
                return [$a['_priority'], $b['_date'], $b['_id']]
                    <=> [$b['_priority'], $a['_date'], $a['_id']];
            })
            ->unique('email')
            ->map(static fn (array $item): array => [
                'email' => $item['email'],
                'name' => $item['name'],
                'vehicle_name' => $item['vehicle_name'],
            ])
            ->values()
            ->all();
    }

    /**
     * Build lightweight priority-sorted audience tuples for live Alpine recipient counting & preview.
     *
     * @return list<array{key: int, name: string, email: string, vehicle: string, product_id: int|null, completed: bool}>
     */
    private function buildPromotionAudienceTuples(): array
    {
        $rows = Appointment::query()
            ->toBase()
            ->leftJoin('users', 'appointments.user_id', '=', 'users.id')
            ->leftJoin('products', 'appointments.product_id', '=', 'products.id')
            ->orderByRaw('CASE WHEN appointments.status = ? THEN 0 ELSE 1 END', [AppointmentStatus::Completed->value])
            ->orderByDesc('appointments.appointment_date')
            ->orderByDesc('appointments.id')
            ->get([
                'appointments.id',
                'appointments.product_id',
                'appointments.status',
                'appointments.meta_data',
                DB::raw('COALESCE(appointments.guest_email, users.email) as resolved_email'),
                DB::raw('COALESCE(users.name, appointments.guest_name) as resolved_name'),
                'products.name as product_name',
            ]);

        $emailKeys = [];
        $nextKey = 0;
        $seenTuples = [];
        $tuples = [];

        foreach ($rows as $row) {
            $email = mb_strtolower(trim((string) ($row->resolved_email ?? '')));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (! isset($emailKeys[$email])) {
                $emailKeys[$email] = ++$nextKey;
            }

            $key = $emailKeys[$email];
            $productId = $row->product_id !== null ? (int) $row->product_id : null;
            $completed = (string) $row->status === AppointmentStatus::Completed->value;
            $signature = $key.'|'.($productId ?? 'none').'|'.($completed ? '1' : '0');

            if (isset($seenTuples[$signature])) {
                continue;
            }

            $meta = is_string($row->meta_data) ? json_decode($row->meta_data, true) : null;
            $vehicleName = trim((string) (
                $row->product_name
                ?? (is_array($meta) ? ($meta['customer_car_model'] ?? '') : '')
            ));

            $seenTuples[$signature] = true;
            $tuples[] = [
                'key' => $key,
                'name' => trim((string) ($row->resolved_name ?? '')) ?: 'Quý khách',
                'email' => $email,
                'vehicle' => $vehicleName !== '' ? $vehicleName : 'Các dòng xe BMW',
                'product_id' => $productId,
                'completed' => $completed,
            ];
        }

        return $tuples;
    }
}

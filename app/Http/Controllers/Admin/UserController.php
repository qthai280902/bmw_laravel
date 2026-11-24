<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Events\StaffAccountUpdated;
use App\Http\Controllers\Controller;
use App\Models\AccessoryOrder;
use App\Models\AdminActivityLog;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Cache\ShowroomCacheService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Danh sách nhân sự nội bộ Showroom & Nhật ký hoạt động gần đây.
     */
    public function index(): View
    {
        $users = User::query()
            ->withCount([
                'assignedAppointments as active_appointments_count' => fn ($q) => $q->whereIn('status', [
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Confirmed->value,
                ]),
                'assignedAccessoryOrders as active_orders_count' => fn ($q) => $q->whereIn('status', [
                    AccessoryOrder::STATUS_PENDING,
                    AccessoryOrder::STATUS_CONFIRMED,
                ]),
            ])
            ->latest()
            ->paginate(20);

        $activityLogs = AdminActivityLog::query()
            ->latest('id')
            ->limit(15)
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => User::roles(),
            'activityLogs' => $activityLogs,
        ]);
    }

    /**
     * Tạo mới tài khoản nhân sự nội bộ (biểu mẫu đầy đủ).
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => User::roles(),
        ]);
    }

    /**
     * Tạo mới tài khoản nhân sự nội bộ.
     */
    public function store(Request $request, ShowroomCacheService $cacheService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:24'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(User::roleValues())],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $user = User::query()->create([
            'name' => trim($validated['name']),
            'email' => $email,
            'phone' => isset($validated['phone']) && trim($validated['phone']) !== '' ? trim($validated['phone']) : null,
            'role' => $validated['role'],
            'job_title' => isset($validated['job_title']) && trim($validated['job_title']) !== '' ? trim($validated['job_title']) : null,
            'department' => isset($validated['department']) && trim($validated['department']) !== '' ? trim($validated['department']) : null,
            'notes' => isset($validated['notes']) && trim($validated['notes']) !== '' ? trim($validated['notes']) : null,
            'is_active' => true,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $cacheService->flushSalesRoster();

        StaffAccountUpdated::dispatch(
            $user,
            'staff.created',
            "Tạo nhân sự mới {$user->name} ({$user->email}) với vai trò {$user->roleLabel()}.",
            $request->user()
        );

        if ($request->boolean('_from_create_page')) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', "Đã tạo hồ sơ nhân sự {$user->name} ({$user->roleLabel()}).");
        }

        return back()->with('success', "Đã tạo nhân sự {$user->name} ({$user->roleLabel()}).");
    }

    /**
     * Trang Chi tiết Thông tin Nhân sự (/admin/users/{user}).
     */
    public function show(Request $request, User $user): View
    {
        $actingUser = $request->user();
        if (! $actingUser || (! $actingUser->canManageSystem() && (int) $actingUser->id !== (int) $user->id)) {
            abort(403, 'Bạn chỉ có quyền xem hồ sơ nhân sự của chính mình.');
        }

        $appointments = Appointment::query()
            ->with(['user:id,name,email,phone', 'product:id,name,price'])
            ->where('assigned_to', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $orders = AccessoryOrder::query()
            ->with(['product:id,name,price'])
            ->where('assigned_to', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $allAssignedItems = $this->normalizeAssignedItems($appointments, $orders);

        $totalLeads = $appointments->count() + $orders->count();
        $openLeads = $appointments->filter(static fn (Appointment $a): bool => in_array($a->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true))->count()
            + $orders->filter(static fn (AccessoryOrder $o): bool => in_array((string) $o->status, [AccessoryOrder::STATUS_PENDING, AccessoryOrder::STATUS_CONFIRMED], true))->count();

        $completedAppointmentsCount = $appointments->where('status', AppointmentStatus::Completed)->count();
        $purchasedVehiclesCount = $appointments
            ->filter(static fn (Appointment $a): bool => $a->status === AppointmentStatus::Completed && $a->completionOutcome() === Appointment::OUTCOME_PURCHASED)
            ->count();

        $completedOrdersCount = $orders->where('status', AccessoryOrder::STATUS_COMPLETED)->count();
        $accessoryRevenue = (int) $orders->completedRevenue();
        $purchasedVehicleValue = (int) $appointments
            ->filter(static fn (Appointment $a): bool => $a->status === AppointmentStatus::Completed && $a->completionOutcome() === Appointment::OUTCOME_PURCHASED)
            ->sum(static fn (Appointment $a): int => (int) ($a->product?->price ?? 0));

        $activityLogs = AdminActivityLog::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(15)
            ->get();

        $canMutate = $actingUser->canManageSystem()
            && (! $user->isTopSuperAdmin() || $actingUser->isTopSuperAdmin());
        $canToggleLock = $canMutate && ! $actingUser->is($user);
        $canViewPerformance = $actingUser->canManageSystem()
            || ($actingUser->isSales() && (int) $actingUser->id === (int) $user->id);

        return view('admin.users.show', [
            'staffUser' => $user,
            'roles' => User::roles(),
            'canMutate' => $canMutate,
            'canToggleLock' => $canToggleLock,
            'canViewPerformance' => $canViewPerformance,
            'kpis' => [
                'total_leads' => $totalLeads,
                'open_leads' => $openLeads,
                'completed_appointments' => $completedAppointmentsCount,
                'purchased_vehicles' => $purchasedVehiclesCount,
                'accessory_orders' => $orders->count(),
                'completed_orders' => $completedOrdersCount,
                'contributed_revenue' => $accessoryRevenue + $purchasedVehicleValue,
                'accessory_revenue' => $accessoryRevenue,
            ],
            'recentItems' => $allAssignedItems->take(15),
            'activityLogs' => $activityLogs,
        ]);
    }

    /**
     * Trang chỉnh sửa hồ sơ & mật khẩu nhân sự.
     */
    public function edit(Request $request, User $user): View
    {
        $actingUser = $request->user();
        $this->assertCanMutateTargetUser($actingUser, $user);

        return view('admin.users.edit', [
            'staffUser' => $user,
            'roles' => User::roles(),
            'isSelf' => $actingUser !== null && $actingUser->is($user),
        ]);
    }

    /**
     * Lưu cập nhật hồ sơ, vai trò & mật khẩu nhân sự.
     */
    public function update(
        Request $request,
        User $user,
        AdminNotificationDispatcher $dispatcher,
        ShowroomCacheService $cacheService,
    ): RedirectResponse {
        $actingUser = $request->user();
        $this->assertCanMutateTargetUser($actingUser, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:24'],
            'role' => ['required', 'string', Rule::in(User::roleValues())],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($actingUser && $actingUser->is($user) && $validated['role'] !== User::ROLE_ADMIN) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Không thể tự hạ vai trò Quản Lý của chính mình xuống Sales hoặc Marketing.'])
                ->with('error', 'Không thể tự hạ vai trò Quản Lý của chính mình xuống Sales hoặc Marketing.');
        }

        $payload = [
            'name' => trim($validated['name']),
            'email' => mb_strtolower(trim($validated['email'])),
            'phone' => isset($validated['phone']) && trim($validated['phone']) !== '' ? trim($validated['phone']) : null,
            'role' => $validated['role'],
            'job_title' => isset($validated['job_title']) && trim($validated['job_title']) !== '' ? trim($validated['job_title']) : null,
            'department' => isset($validated['department']) && trim($validated['department']) !== '' ? trim($validated['department']) : null,
            'notes' => isset($validated['notes']) && trim($validated['notes']) !== '' ? trim($validated['notes']) : null,
        ];

        $passwordChanged = false;
        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
            $passwordChanged = true;
        }

        $user->update($payload);

        $dispatcher->forgetDigestCache((int) $user->id);
        $cacheService->flushSalesRoster();

        $summary = "Cập nhật hồ sơ nhân sự {$user->name} ({$user->email} · {$user->roleLabel()})"
            .($passwordChanged ? ' kèm đặt lại mật khẩu.' : '.');

        StaffAccountUpdated::dispatch(
            $user->fresh() ?? $user,
            'staff.updated',
            $summary,
            $actingUser,
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "Đã cập nhật thông tin nhân sự {$user->name}.");
    }

    /**
     * Cập nhật vai trò nhân sự (chặn tự hạ quyền Quản Lý của chính mình & bảo vệ Top Super Admin).
     */
    public function updateRole(
        Request $request,
        User $user,
        AdminNotificationDispatcher $dispatcher,
        ShowroomCacheService $cacheService
    ): RedirectResponse {
        $actingUser = $request->user();
        $this->assertCanMutateTargetUser($actingUser, $user);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(User::roleValues())],
        ]);

        if ($actingUser && $actingUser->is($user) && $validated['role'] !== User::ROLE_ADMIN) {
            return back()
                ->withErrors(['role' => 'Không thể tự hạ vai trò Quản Lý của chính mình xuống Sales hoặc Marketing.'])
                ->with('error', 'Không thể tự hạ vai trò Quản Lý của chính mình xuống Sales hoặc Marketing.');
        }

        $oldRoleLabel = $user->roleLabel();

        $user->update([
            'role' => $validated['role'],
        ]);

        $dispatcher->forgetDigestCache((int) $user->id);
        $cacheService->flushSalesRoster();

        StaffAccountUpdated::dispatch(
            $user->fresh() ?? $user,
            'staff.role_updated',
            "Đổi vai trò nhân sự {$user->name} ({$user->email}) từ {$oldRoleLabel} sang {$user->roleLabel()}.",
            $actingUser
        );

        return back()->with('success', "Đã cập nhật vai trò cho {$user->name} thành {$user->roleLabel()}.");
    }

    /**
     * Khóa / Mở khóa tài khoản nhân sự (chặn tự khóa chính mình & bảo vệ Top Super Admin).
     */
    public function toggleActive(
        Request $request,
        User $user,
        AdminNotificationDispatcher $dispatcher,
        ShowroomCacheService $cacheService,
    ): RedirectResponse {
        $actingUser = $request->user();
        $this->assertCanMutateTargetUser($actingUser, $user);

        if ($actingUser && $actingUser->is($user)) {
            return back()
                ->withErrors(['is_active' => 'Không thể tự khóa tài khoản của chính mình.'])
                ->with('error', 'Không thể tự khóa tài khoản của chính mình.');
        }

        $nextActive = ! $user->isActiveAccount();
        $user->update(['is_active' => $nextActive]);

        $dispatcher->forgetDigestCache((int) $user->id);
        $cacheService->flushSalesRoster();

        StaffAccountUpdated::dispatch(
            $user->fresh() ?? $user,
            $nextActive ? 'staff.unlocked' : 'staff.locked',
            ($nextActive ? 'Mở khóa tài khoản nhân sự ' : 'Khóa tạm thời tài khoản nhân sự ')."{$user->name} ({$user->email}).",
            $actingUser,
        );

        return back()->with(
            'success',
            $nextActive
                ? "Đã mở khóa hoạt động cho tài khoản {$user->name}."
                : "Đã khóa tài khoản {$user->name}. Nhân sự này sẽ không thể đăng nhập hoặc nhận Lead tự động."
        );
    }

    private function assertCanMutateTargetUser(?User $actingUser, User $targetUser): void
    {
        if (! $actingUser || ! $actingUser->canManageSystem()) {
            abort(403, 'Bạn không có quyền quản trị tài khoản nhân sự.');
        }

        if ($targetUser->isTopSuperAdmin() && ! $actingUser->isTopSuperAdmin()) {
            abort(403, 'Chỉ Top Super Admin mới có quyền chỉnh sửa, đổi mật khẩu, đổi vai trò hoặc khóa tài khoản Top Super Admin.');
        }
    }

    /**
     * Trang thống kê & biểu đồ hiệu suất làm việc của Sales (Admin xem tất cả; Sales chỉ xem của mình).
     */
    public function performance(Request $request, User $user, ShowroomCacheService $cacheService): View
    {
        $actingUser = $request->user();
        if (! $actingUser || (! $actingUser->canManageSystem() && (int) $actingUser->id !== (int) $user->id)) {
            abort(403, 'Bạn chỉ có quyền xem thống kê hiệu suất cá nhân của mình.');
        }

        $tz = 'Asia/Ho_Chi_Minh';
        $nowVn = CarbonImmutable::now($tz);

        $period = (string) $request->input('period', '30d');
        $allowedPeriods = ['7d', '30d', 'this_month', 'all', 'custom'];
        if (! in_array($period, $allowedPeriods, true)) {
            $period = '30d';
        }

        $dateFromInput = (string) $request->input('date_from', '');
        $dateToInput = (string) $request->input('date_to', '');

        $statusFilter = (string) $request->input('status', '');
        if (! in_array($statusFilter, ['', 'pending', 'confirmed', 'completed', 'cancelled'], true)) {
            $statusFilter = '';
        }

        $kindFilter = (string) $request->input('kind', 'all');
        if (! in_array($kindFilter, ['all', 'appointment', 'service', 'accessory'], true)) {
            $kindFilter = 'all';
        }

        [$startVn, $endVn] = match ($period) {
            '7d' => [$nowVn->subDays(6)->startOfDay(), $nowVn->endOfDay()],
            '30d' => [$nowVn->subDays(29)->startOfDay(), $nowVn->endOfDay()],
            'this_month' => [$nowVn->startOfMonth(), $nowVn->endOfDay()],
            'custom' => [
                $this->parseDateSafely($dateFromInput, $tz)?->startOfDay(),
                $this->parseDateSafely($dateToInput, $tz)?->endOfDay(),
            ],
            default => [null, null],
        };

        $startUtc = $startVn?->setTimezone('UTC');
        $endUtc = $endVn?->setTimezone('UTC');

        $appointments = Appointment::query()
            ->with(['user:id,name,email', 'product:id,name'])
            ->where('assigned_to', $user->id)
            ->when($startUtc, fn ($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn ($q) => $q->where('created_at', '<=', $endUtc))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $orders = AccessoryOrder::query()
            ->with(['product:id,name,price'])
            ->where('assigned_to', $user->id)
            ->when($startUtc, fn ($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn ($q) => $q->where('created_at', '<=', $endUtc))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $allItems = $this->normalizeAssignedItems($appointments, $orders);

        $appointmentCounts = $appointments->statusCounts();
        $orderCounts = $orders->statusCounts();

        $totalLeads = $appointmentCounts['total'] + $orderCounts['total'];
        $completedCount = $appointmentCounts['completed'] + $orderCounts['completed'];
        $confirmedCount = $appointmentCounts['confirmed'] + $orderCounts['confirmed'];
        $pendingCount = $appointmentCounts['pending'] + $orderCounts['pending'];
        $cancelledCount = $appointmentCounts['cancelled'] + $orderCounts['cancelled'];

        $completionRate = $totalLeads > 0 ? round(($completedCount / $totalLeads) * 100, 1) : 0.0;
        $accessoryRevenue = (float) $orders->completedRevenue();

        $series = $this->buildTimelineSeries($allItems, $startVn, $endVn, $nowVn, $tz);
        $donutSlices = $this->buildDonutSlices($pendingCount, $confirmedCount, $completedCount, $cancelledCount, $totalLeads);

        $filteredItems = $allItems
            ->when($statusFilter !== '', fn (Collection $col) => $col->where('status', $statusFilter))
            ->when($kindFilter !== 'all', fn (Collection $col) => $col->where('kind', $kindFilter))
            ->values();

        return view('admin.users.performance', [
            'staffUser' => $user,
            'salesRoster' => $actingUser->canManageSystem() ? $cacheService->salesRoster() : collect([$user]),
            'period' => $period,
            'dateFrom' => $dateFromInput !== '' ? $dateFromInput : ($startVn?->format('Y-m-d') ?? ''),
            'dateTo' => $dateToInput !== '' ? $dateToInput : ($endVn?->format('Y-m-d') ?? ''),
            'statusFilter' => $statusFilter,
            'kindFilter' => $kindFilter,
            'kpis' => [
                'total' => $totalLeads,
                'completed' => $completedCount,
                'confirmed' => $confirmedCount,
                'pending' => $pendingCount,
                'cancelled' => $cancelledCount,
                'completion_rate' => $completionRate,
                'accessory_revenue' => $accessoryRevenue,
                'appointments_count' => $allItems->where('kind', 'appointment')->count(),
                'service_count' => $allItems->where('kind', 'service')->count(),
                'accessory_count' => $allItems->where('kind', 'accessory')->count(),
            ],
            'series' => $series,
            'donutSlices' => $donutSlices,
            'items' => $filteredItems->take(50),
            'totalFilteredCount' => $filteredItems->count(),
        ]);
    }

    private function parseDateSafely(string $raw, string $tz): ?CarbonImmutable
    {
        if ($raw === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw, $tz);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Chuẩn hóa danh sách Lịch hẹn / Dịch vụ / Đơn phụ kiện về một tập dữ liệu thống nhất.
     *
     * @param  Collection<int, Appointment>  $appointments
     * @param  Collection<int, AccessoryOrder>  $orders
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizeAssignedItems(Collection $appointments, Collection $orders): Collection
    {
        $items = collect();

        foreach ($appointments as $appointment) {
            $kind = $appointment->isServiceBooking() ? 'service' : 'appointment';
            $status = $appointment->status->value;

            $items->push([
                'kind' => $kind,
                'kind_label' => $kind === 'service' ? 'Dịch vụ xe' : 'Lịch hẹn Showroom',
                'id' => $appointment->id,
                'customer_order_code' => $appointment->customer_order_code ?: '#BMW-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT),
                'daily_sequence_code' => $appointment->daily_sequence_code ?: '—',
                'customer_name' => $appointment->customer_name,
                'customer_phone' => $appointment->customer_phone,
                'subject' => $appointment->isServiceBooking()
                    ? ($appointment->servicePackageLabel() ?: ($appointment->customer_car_model ?: 'Bảo dưỡng định kỳ'))
                    : ($appointment->product?->name ?? $appointment->typeLabel()),
                'status' => $status,
                'status_label' => $appointment->status->label(),
                'created_at' => $appointment->created_at,
                'route_url' => $kind === 'service'
                    ? route('admin.service-bookings.index', ['search' => $appointment->customer_order_code ?: $appointment->customer_phone])
                    : route('admin.appointments.index', ['search' => $appointment->customer_order_code ?: $appointment->customer_phone]),
            ]);
        }

        foreach ($orders as $order) {
            $items->push([
                'kind' => 'accessory',
                'kind_label' => 'Đơn phụ kiện',
                'id' => $order->id,
                'customer_order_code' => $order->customer_order_code ?: '#ACC-'.$order->id,
                'daily_sequence_code' => $order->daily_sequence_code ?: '—',
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'subject' => ($order->product?->name ?? 'Phụ kiện chính hãng').' × '.$order->quantity,
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'created_at' => $order->created_at,
                'route_url' => route('admin.accessory-orders.show', $order),
            ]);
        }

        return $items->sortByDesc(fn (array $item) => $item['created_at']?->timestamp ?? 0)->values();
    }

    /**
     * Xây dựng chuỗi cột nhóm theo ngày hoặc theo tháng (khi xem dài ngày).
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function buildTimelineSeries(
        Collection $items,
        ?CarbonImmutable $startVn,
        ?CarbonImmutable $endVn,
        CarbonImmutable $nowVn,
        string $tz
    ): array {
        $effectiveStart = $startVn ?? $nowVn->subDays(13)->startOfDay();
        $effectiveEnd = $endVn ?? $nowVn->endOfDay();

        $totalDays = max(1, (int) $effectiveStart->diffInDays($effectiveEnd) + 1);
        $groupByMonth = $totalDays > 35;

        $buckets = [];

        if ($groupByMonth) {
            $cursor = $effectiveStart->startOfMonth();
            $limit = 0;
            while ($cursor->lte($effectiveEnd) && $limit < 12) {
                $key = $cursor->format('Y-m');
                $buckets[$key] = [
                    'key' => $key,
                    'label' => 'T'.$cursor->format('m/y'),
                    'full_label' => 'Tháng '.$cursor->format('m/Y'),
                    'appointment' => 0,
                    'service' => 0,
                    'accessory' => 0,
                ];
                $cursor = $cursor->addMonth();
                $limit++;
            }
        } else {
            $step = $totalDays > 14 ? (int) ceil($totalDays / 10) : 1;
            if ($step === 1) {
                $cursor = $effectiveStart->startOfDay();
                $limit = 0;
                while ($cursor->lte($effectiveEnd) && $limit < 14) {
                    $key = $cursor->format('Y-m-d');
                    $buckets[$key] = [
                        'key' => $key,
                        'label' => $cursor->format('d/m'),
                        'full_label' => $cursor->format('d/m/Y'),
                        'appointment' => 0,
                        'service' => 0,
                        'accessory' => 0,
                    ];
                    $cursor = $cursor->addDay();
                    $limit++;
                }
            } else {
                $cursor = $effectiveStart->startOfDay();
                $idx = 0;
                while ($cursor->lte($effectiveEnd) && $idx < 10) {
                    $bucketEnd = $cursor->addDays($step - 1)->endOfDay();
                    if ($bucketEnd->gt($effectiveEnd)) {
                        $bucketEnd = $effectiveEnd;
                    }
                    $key = 'range_'.$idx;
                    $buckets[$key] = [
                        'key' => $key,
                        'start_ts' => $cursor->timestamp,
                        'end_ts' => $bucketEnd->timestamp,
                        'label' => $cursor->format('d/m'),
                        'full_label' => $cursor->format('d/m').' – '.$bucketEnd->format('d/m/Y'),
                        'appointment' => 0,
                        'service' => 0,
                        'accessory' => 0,
                    ];
                    $cursor = $bucketEnd->addSecond()->startOfDay();
                    $idx++;
                }
            }
        }

        foreach ($items as $item) {
            $created = $item['created_at']?->setTimezone($tz);
            if (! $created) {
                continue;
            }

            if ($groupByMonth) {
                $key = $created->format('Y-m');
                if (isset($buckets[$key])) {
                    $buckets[$key][$item['kind']]++;
                }
            } elseif (! empty($buckets) && isset(reset($buckets)['start_ts'])) {
                $ts = $created->timestamp;
                foreach ($buckets as $bKey => $bucket) {
                    if ($ts >= $bucket['start_ts'] && $ts <= $bucket['end_ts']) {
                        $buckets[$bKey][$item['kind']]++;
                        break;
                    }
                }
            } else {
                $key = $created->format('Y-m-d');
                if (isset($buckets[$key])) {
                    $buckets[$key][$item['kind']]++;
                }
            }
        }

        return array_values($buckets);
    }

    /**
     * Tính thông số SVG Donut (chu vi vòng tròn bán kính r=42).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildDonutSlices(int $pending, int $confirmed, int $completed, int $cancelled, int $total): array
    {
        $radius = 42.0;
        $circumference = round(2 * M_PI * $radius, 2);

        $raw = [
            ['key' => 'completed', 'label' => 'Hoàn thành', 'count' => $completed, 'color' => '#10B981'],
            ['key' => 'confirmed', 'label' => 'Đã xác nhận', 'count' => $confirmed, 'color' => '#38BDF8'],
            ['key' => 'pending', 'label' => 'Chờ duyệt', 'count' => $pending, 'color' => '#F59E0B'],
            ['key' => 'cancelled', 'label' => 'Đã hủy', 'count' => $cancelled, 'color' => '#F43F5E'],
        ];

        $offset = 0.0;
        $slices = [];

        foreach ($raw as $entry) {
            $pct = $total > 0 ? round(($entry['count'] / $total) * 100, 1) : 0.0;
            $dash = $total > 0 ? round(($entry['count'] / $total) * $circumference, 2) : 0.0;

            $slices[] = [
                'key' => $entry['key'],
                'label' => $entry['label'],
                'count' => $entry['count'],
                'percent' => $pct,
                'color' => $entry['color'],
                'dash' => $dash,
                'gap' => max(0.0, round($circumference - $dash, 2)),
                'offset' => round(-$offset, 2),
                'circumference' => $circumference,
            ];

            $offset += $dash;
        }

        return $slices;
    }
}

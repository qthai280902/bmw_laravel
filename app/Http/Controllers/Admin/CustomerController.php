<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Events\PromotionCampaignDispatched;
use App\Http\Controllers\Controller;
use App\Mail\Customer\TemplatedCustomerMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\CustomerInstallmentPlan;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\InteractionTimelineRecorder;
use App\Services\Crm\SalesAssignmentService;
use App\Services\Mail\EmailTemplateRenderer;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request, ShowroomCacheService $cacheService): View
    {
        $search = trim((string) $request->query('search', ''));
        $rawStatus = trim((string) $request->query('status', ''));
        $validStatuses = array_column(AppointmentStatus::cases(), 'value');
        $statusFilter = in_array($rawStatus, $validStatuses, true) ? $rawStatus : '';
        $productIdFilter = $request->filled('product_id') && is_numeric($request->query('product_id'))
            ? (int) $request->query('product_id')
            : null;
        $salesIdFilter = trim((string) $request->query('sales_id', ''));
        $recentFilter = trim((string) $request->query('recent', ''));
        if (! in_array($recentFilter, ['', '7d', '30d', 'this_month'], true)) {
            $recentFilter = '';
        }
        $leadPriorityFilter = trim((string) $request->query('lead_priority', ''));
        if (! in_array($leadPriorityFilter, ['', Appointment::LEAD_PRIORITY_HOT, Appointment::LEAD_PRIORITY_WARM, Appointment::LEAD_PRIORITY_NORMAL], true)) {
            $leadPriorityFilter = '';
        }

        $recentStart = match ($recentFilter) {
            '7d' => CarbonImmutable::now('Asia/Ho_Chi_Minh')->subDays(6)->startOfDay()->setTimezone(config('app.timezone', 'UTC')),
            '30d' => CarbonImmutable::now('Asia/Ho_Chi_Minh')->subDays(29)->startOfDay()->setTimezone(config('app.timezone', 'UTC')),
            'this_month' => CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfMonth()->setTimezone(config('app.timezone', 'UTC')),
            default => null,
        };

        $customers = Appointment::select(
            DB::raw('COALESCE(appointments.guest_email, users.email) as email'),
            DB::raw('MAX(COALESCE(appointments.guest_name, users.name)) as name'),
            DB::raw("MAX(COALESCE(appointments.guest_phone, 'N/A')) as phone"),
            DB::raw('COUNT(*) as interactions_count'),
            DB::raw('MAX(appointments.appointment_date) as last_interaction'),
            DB::raw('MAX(assigned_sales.name) as assigned_sales_name'),
            DB::raw("MIN(CASE appointments.lead_priority WHEN 'hot' THEN 1 WHEN 'warm' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END) as priority_rank")
        )
            ->leftJoin('users', 'appointments.user_id', '=', 'users.id')
            ->leftJoin('users as assigned_sales', 'appointments.assigned_to', '=', 'assigned_sales.id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('appointments.guest_name', 'like', "%{$search}%")
                        ->orWhere('appointments.guest_email', 'like', "%{$search}%")
                        ->orWhere('appointments.guest_phone', 'like', "%{$search}%")
                        ->orWhere('appointments.customer_order_code', 'like', "%{$search}%")
                        ->orWhere('appointments.daily_sequence_code', 'like', "%{$search}%")
                        ->orWhere('appointments.customer_internal_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($statusFilter !== '', fn ($query) => $query->where('appointments.status', $statusFilter))
            ->when($productIdFilter !== null, fn ($query) => $query->where('appointments.product_id', $productIdFilter))
            ->when($leadPriorityFilter !== '', fn ($query) => $query->where('appointments.lead_priority', $leadPriorityFilter))
            ->when($recentStart !== null, fn ($query) => $query->where('appointments.appointment_date', '>=', $recentStart->toDateTimeString()))
            ->when($salesIdFilter !== '', function ($query) use ($salesIdFilter) {
                if ($salesIdFilter === 'mine') {
                    $query->where('appointments.assigned_to', Auth::id());
                } elseif ($salesIdFilter === 'unassigned') {
                    $query->whereNull('appointments.assigned_to');
                } elseif (is_numeric($salesIdFilter)) {
                    $query->where('appointments.assigned_to', (int) $salesIdFilter);
                }
            })
            ->groupBy(DB::raw('COALESCE(appointments.guest_email, users.email)'))
            ->orderBy('last_interaction', 'desc')
            ->paginate(15)
            ->withQueryString();

        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $statuses = AppointmentStatus::cases();
        $salesRoster = $cacheService->salesRoster();
        $unassignedTotal = Appointment::query()->whereNull('assigned_to')->count()
            + AccessoryOrder::query()->whereNull('assigned_to')->count();

        [$installmentEmails, $installmentPhones] = $this->openInstallmentIdentitySets();

        $promotionAudienceTuples = Auth::user()?->canAccessContent()
            ? $this->buildPromotionAudienceTuples($installmentEmails, $installmentPhones)
            : [];

        $activeEmailTemplates = Auth::user()?->canAccessContent()
            ? EmailTemplate::query()
                ->active()
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'category', 'subject', 'body_content'])
            : collect();

        $activeFilterCount = collect([
            $search !== '',
            $statusFilter !== '',
            $productIdFilter !== null,
            $salesIdFilter !== '',
            $recentFilter !== '',
            $leadPriorityFilter !== '',
        ])->filter()->count();

        return view('admin.customers.index', compact(
            'customers',
            'products',
            'statuses',
            'statusFilter',
            'productIdFilter',
            'salesIdFilter',
            'recentFilter',
            'leadPriorityFilter',
            'salesRoster',
            'unassignedTotal',
            'activeFilterCount',
            'promotionAudienceTuples',
            'activeEmailTemplates',
            'installmentEmails',
            'installmentPhones',
        ));
    }

    public function show(Request $request, string $phone, EmailTemplateRenderer $renderer): View
    {
        $emailQuery = trim((string) $request->query('email', ''));
        $bundle = $this->resolveCustomerBundle($phone, $emailQuery);

        $this->assertSalesCanAccessCustomer($bundle['appointments'], $bundle['accessoryOrders'], $bundle['installmentPlans']);

        $activeEmailTemplates = EmailTemplate::query()
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $vehicleProducts = Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $primaryInstallment = $bundle['installmentPlans']
            ->first(static fn (CustomerInstallmentPlan $p): bool => in_array($p->status, [CustomerInstallmentPlan::STATUS_ACTIVE, CustomerInstallmentPlan::STATUS_OVERDUE], true))
            ?? $bundle['installmentPlans']->first();

        $latestAppointment = $bundle['appointments']->first();
        $defaultVehicleName = $latestAppointment?->product?->name
            ?? $primaryInstallment?->product?->name
            ?? 'Các dòng xe BMW';
        $defaultAdvisorName = $latestAppointment?->assignedSales?->name
            ?? Auth::user()?->name
            ?? 'Đội ngũ Chuyên viên BMW Showroom';

        $previewVariables = $renderer->buildVariables(
            customerName: $bundle['profile']['name'],
            vehicleName: $defaultVehicleName,
            advisorName: $defaultAdvisorName,
            installmentPlan: $primaryInstallment,
        );

        return view('admin.customers.show', [
            'profile' => $bundle['profile'],
            'routePhone' => $bundle['routePhone'],
            'routeEmail' => $bundle['routeEmail'],
            'vehicleAppointments' => $bundle['vehicleAppointments'],
            'serviceBookings' => $bundle['serviceBookings'],
            'accessoryOrders' => $bundle['accessoryOrders'],
            'installmentPlans' => $bundle['installmentPlans'],
            'aiSessions' => $bundle['aiSessions'],
            'kpis' => $bundle['kpis'],
            'activeEmailTemplates' => $activeEmailTemplates,
            'vehicleProducts' => $vehicleProducts,
            'installmentStatusOptions' => CustomerInstallmentPlan::statusOptions(),
            'previewVariables' => $previewVariables,
        ]);
    }

    public function storeInstallment(Request $request, string $phone): RedirectResponse
    {
        $emailQuery = trim((string) ($request->input('route_email') ?? $request->query('email', '')));
        $bundle = $this->resolveCustomerBundle($phone, $emailQuery);
        $this->assertSalesCanAccessCustomer($bundle['appointments'], $bundle['accessoryOrders'], $bundle['installmentPlans']);

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:150'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')],
            'loan_amount' => ['required', 'integer', 'min:1000000'],
            'monthly_payment' => ['required', 'integer', 'min:100000'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'term_months' => ['required', 'integer', 'min:1', 'max:240'],
            'paid_installments' => ['required', 'integer', 'min:0', 'lte:term_months'],
            'next_due_date' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(array_keys(CustomerInstallmentPlan::statusOptions()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'bank_name.required' => 'Vui lòng nhập tên ngân hàng / tổ chức tài chính.',
            'loan_amount.required' => 'Vui lòng nhập tổng dư nợ vay (VNĐ).',
            'monthly_payment.required' => 'Vui lòng nhập số tiền thanh toán hàng tháng (VNĐ).',
            'paid_installments.lte' => 'Số kỳ đã thanh toán không được vượt quá tổng số kỳ hạn.',
        ]);

        $normalizedPhone = $bundle['profile']['phone_digits'] !== ''
            ? $bundle['profile']['phone_digits']
            : ($bundle['profile']['phone'] !== 'N/A' ? $bundle['profile']['phone'] : $phone);

        $plan = CustomerInstallmentPlan::query()->create([
            'customer_phone' => $normalizedPhone,
            'customer_email' => $bundle['profile']['email'] !== '' ? $bundle['profile']['email'] : null,
            'customer_name' => $bundle['profile']['name'],
            'product_id' => $validated['product_id'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'bank_name' => trim((string) $validated['bank_name']),
            'loan_amount' => (int) $validated['loan_amount'],
            'monthly_payment' => (int) $validated['monthly_payment'],
            'interest_rate' => (float) $validated['interest_rate'],
            'term_months' => (int) $validated['term_months'],
            'paid_installments' => (int) $validated['paid_installments'],
            'next_due_date' => $validated['next_due_date'] ?? null,
            'status' => (string) $validated['status'],
            'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
            'created_by' => Auth::id(),
        ]);

        $latestAppointment = $bundle['appointments']->first();
        if ($latestAppointment instanceof Appointment) {
            InteractionTimelineRecorder::record(
                $latestAppointment,
                InteractionTimelineRecorder::ACTOR_ADMIN,
                InteractionTimelineRecorder::adminActorLabel(Auth::user()),
                "Khởi tạo hồ sơ trả góp ({$plan->bank_name})",
                'Dư nợ '.number_format($plan->loan_amount, 0, ',', '.').' VNĐ · '.$plan->term_months.' tháng · Lãi suất '.$plan->interest_rate.'%/năm.',
                'BMW Financial',
            );
        }

        return redirect()
            ->route('admin.customers.show', $this->customerShowRouteParams($bundle['routePhone'], $bundle['routeEmail']))
            ->with('success', "Đã tạo hồ sơ trả góp tại {$plan->bank_name} cho khách hàng {$bundle['profile']['name']}.");
    }

    public function updateInstallment(Request $request, string $phone, CustomerInstallmentPlan $plan): RedirectResponse
    {
        $emailQuery = trim((string) ($request->input('route_email') ?? $request->query('email', '')));
        $bundle = $this->resolveCustomerBundle($phone, $emailQuery);
        $this->assertSalesCanAccessCustomer($bundle['appointments'], $bundle['accessoryOrders'], $bundle['installmentPlans']);

        if (! $bundle['installmentPlans']->contains('id', $plan->id)) {
            abort(404);
        }

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:150'],
            'loan_amount' => ['required', 'integer', 'min:1000000'],
            'monthly_payment' => ['required', 'integer', 'min:100000'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'term_months' => ['required', 'integer', 'min:1', 'max:240'],
            'paid_installments' => ['required', 'integer', 'min:0', 'lte:term_months'],
            'next_due_date' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(array_keys(CustomerInstallmentPlan::statusOptions()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'paid_installments.lte' => 'Số kỳ đã thanh toán không được vượt quá tổng số kỳ hạn.',
        ]);

        $plan->update([
            'bank_name' => trim((string) $validated['bank_name']),
            'loan_amount' => (int) $validated['loan_amount'],
            'monthly_payment' => (int) $validated['monthly_payment'],
            'interest_rate' => (float) $validated['interest_rate'],
            'term_months' => (int) $validated['term_months'],
            'paid_installments' => (int) $validated['paid_installments'],
            'next_due_date' => $validated['next_due_date'] ?? null,
            'status' => (string) $validated['status'],
            'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
        ]);

        $latestAppointment = $bundle['appointments']->first();
        if ($latestAppointment instanceof Appointment) {
            InteractionTimelineRecorder::record(
                $latestAppointment,
                InteractionTimelineRecorder::ACTOR_ADMIN,
                InteractionTimelineRecorder::adminActorLabel(Auth::user()),
                "Cập nhật tiến độ trả góp ({$plan->bank_name})",
                "Đã đóng {$plan->paid_installments}/{$plan->term_months} kỳ · Lãi suất {$plan->interest_rate}%/năm · Trạng thái: {$plan->statusLabel()}.",
                'BMW Financial',
            );
        }

        return redirect()
            ->route('admin.customers.show', $this->customerShowRouteParams($bundle['routePhone'], $bundle['routeEmail']))
            ->with('success', "Đã cập nhật hồ sơ trả góp {$plan->bank_name} ({$plan->paid_installments}/{$plan->term_months} kỳ).");
    }

    public function sendQuickEmail(Request $request, string $phone, EmailTemplateRenderer $renderer): RedirectResponse
    {
        $emailQuery = trim((string) ($request->input('route_email') ?? $request->query('email', '')));
        $bundle = $this->resolveCustomerBundle($phone, $emailQuery);
        $this->assertSalesCanAccessCustomer($bundle['appointments'], $bundle['accessoryOrders'], $bundle['installmentPlans']);

        $validated = $request->validate([
            'template_id' => ['required', 'integer', Rule::exists('email_templates', 'id')],
            'recipient_email' => ['required', 'email', 'max:190'],
            'installment_plan_id' => ['nullable', 'integer'],
            'subject_override' => ['nullable', 'string', 'max:220'],
            'body_override' => ['nullable', 'string', 'max:10000'],
        ], [
            'template_id.required' => 'Vui lòng chọn mẫu Email cần gửi.',
            'recipient_email.required' => 'Khách hàng chưa có địa chỉ Email hợp lệ để gửi thư.',
            'recipient_email.email' => 'Địa chỉ Email người nhận không hợp lệ.',
        ]);

        $template = EmailTemplate::query()->findOrFail((int) $validated['template_id']);
        if (! $template->isActive()) {
            return back()
                ->withInput()
                ->withErrors(['template_id' => 'Chỉ được phép gửi những mẫu Email đang ở trạng thái Đang dùng (Active).']);
        }

        $selectedPlan = null;
        if (! empty($validated['installment_plan_id'])) {
            $selectedPlan = $bundle['installmentPlans']->firstWhere('id', (int) $validated['installment_plan_id']);
        }
        if (! $selectedPlan instanceof CustomerInstallmentPlan) {
            $selectedPlan = $bundle['installmentPlans']
                ->first(static fn (CustomerInstallmentPlan $p): bool => in_array($p->status, [CustomerInstallmentPlan::STATUS_ACTIVE, CustomerInstallmentPlan::STATUS_OVERDUE], true))
                ?? $bundle['installmentPlans']->first();
        }

        $latestAppointment = $bundle['appointments']->first();
        $vehicleName = $selectedPlan?->product?->name
            ?? $latestAppointment?->product?->name
            ?? 'Các dòng xe BMW';
        $advisorName = Auth::user()?->name
            ?? $latestAppointment?->assignedSales?->name
            ?? 'Đội ngũ Chuyên viên BMW Showroom';

        $variables = $renderer->buildVariables(
            customerName: $bundle['profile']['name'],
            vehicleName: $vehicleName,
            advisorName: $advisorName,
            installmentPlan: $selectedPlan,
        );

        $recipientEmail = trim((string) $validated['recipient_email']);

        Mail::to($recipientEmail)->queue(new TemplatedCustomerMail(
            template: $template,
            variables: $variables,
            subjectOverride: isset($validated['subject_override']) && trim((string) $validated['subject_override']) !== ''
                ? trim((string) $validated['subject_override'])
                : null,
            bodyOverride: isset($validated['body_override']) && trim((string) $validated['body_override']) !== ''
                ? trim((string) $validated['body_override'])
                : null,
        ));

        if ($latestAppointment instanceof Appointment) {
            InteractionTimelineRecorder::record(
                $latestAppointment,
                InteractionTimelineRecorder::ACTOR_ADMIN,
                InteractionTimelineRecorder::adminActorLabel(Auth::user()),
                "Gửi Email từ mẫu: {$template->name}",
                "Đã xếp hàng đợi gửi thư chăm sóc 1-1 tới {$recipientEmail} (Mã mẫu: {$template->code}).",
                'Email 1-1',
            );
        }

        return redirect()
            ->route('admin.customers.show', $this->customerShowRouteParams($bundle['routePhone'], $bundle['routeEmail']))
            ->with('success', "Đã xếp hàng đợi gửi Email \"{$template->name}\" tới {$recipientEmail}.");
    }

    public function distributeSales(Request $request, SalesAssignmentService $assignmentService): RedirectResponse
    {
        $counts = $assignmentService->distributeUnassignedEvenly($request->user());
        $totalDistributed = $counts['appointments'] + $counts['accessory_orders'];

        if ($totalDistributed === 0) {
            return redirect()
                ->route('admin.customers.index')
                ->with('success', 'Không có hồ sơ chưa phân công hoặc chưa có nhân viên Sales hoạt động để chia tải.');
        }

        return redirect()
            ->route('admin.customers.index')
            ->with('success', "Đã chia đều {$counts['appointments']} lịch hẹn và {$counts['accessory_orders']} đơn phụ kiện chưa phân công cho đội Sales.");
    }

    public function sendPromotions(Request $request): RedirectResponse
    {
        if ($request->has('product_id') && trim((string) $request->input('product_id')) === '') {
            $request->merge(['product_id' => null]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:4000'],
            'audience' => ['required', 'string', Rule::in(['all', 'completed', 'by_product', 'installment'])],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
        ]);

        if ($validated['audience'] === 'by_product' && empty($validated['product_id'])) {
            return back()
                ->withInput()
                ->withErrors(['product_id' => 'Vui lòng chọn mẫu xe cụ thể để lọc khách hàng mục tiêu.']);
        }

        $productId = ! empty($validated['product_id']) ? (int) $validated['product_id'] : null;
        $onlyCompleted = $validated['audience'] === 'completed' || $request->boolean('only_completed');
        $onlyInstallment = $validated['audience'] === 'installment';

        $recipients = $this->resolvePromotionRecipients($productId, $onlyCompleted, $onlyInstallment);

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
     * Resolve customer 360° bundle from route {phone} and optional ?email= query.
     *
     * @return array{
     *     profile: array{name: string, phone: string, phone_digits: string, email: string, primary_sales: string},
     *     routePhone: string,
     *     routeEmail: string,
     *     appointments: Collection<int, Appointment>,
     *     vehicleAppointments: Collection<int, Appointment>,
     *     serviceBookings: Collection<int, Appointment>,
     *     accessoryOrders: Collection<int, AccessoryOrder>,
     *     installmentPlans: Collection<int, CustomerInstallmentPlan>,
     *     aiSessions: Collection<int, AiChatSession>,
     *     kpis: array{appointments: int, services: int, orders: int, total_spend: int, open_installments: int, ai_sessions: int}
     * }
     */
    private function resolveCustomerBundle(string $phoneParam, string $emailQuery): array
    {
        $decodedPhone = trim(urldecode($phoneParam));
        $phoneDigits = CustomerInstallmentPlan::normalizePhoneDigits($decodedPhone);
        $emailLower = mb_strtolower(trim($emailQuery));

        $appointments = Appointment::query()
            ->with(['user:id,name,email', 'product:id,name,price,slug', 'assignedSales:id,name,role'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get()
            ->filter(function (Appointment $appointment) use ($phoneDigits, $emailLower): bool {
                $appPhoneDigits = CustomerInstallmentPlan::normalizePhoneDigits($appointment->guest_phone);
                $appEmail = mb_strtolower(trim((string) ($appointment->guest_email ?? $appointment->user?->email ?? '')));

                if ($phoneDigits !== '' && $appPhoneDigits !== '' && $appPhoneDigits === $phoneDigits) {
                    return true;
                }

                if ($emailLower !== '' && $appEmail !== '' && $appEmail === $emailLower) {
                    return true;
                }

                return false;
            })
            ->values();

        // Collect all known emails & phone digits of this customer for complete 360° cross-matching
        $knownEmails = $appointments
            ->map(static fn (Appointment $a): string => mb_strtolower(trim((string) ($a->guest_email ?? $a->user?->email ?? ''))))
            ->filter()
            ->when($emailLower !== '', static fn (Collection $c): Collection => $c->push($emailLower))
            ->unique()
            ->values()
            ->all();

        $knownPhoneDigits = $appointments
            ->map(static fn (Appointment $a): string => CustomerInstallmentPlan::normalizePhoneDigits($a->guest_phone))
            ->filter()
            ->when($phoneDigits !== '', static fn (Collection $c): Collection => $c->push($phoneDigits))
            ->unique()
            ->values()
            ->all();

        $accessoryOrders = AccessoryOrder::query()
            ->with(['product:id,name,price,category_id', 'product.category:id,name', 'assignedSales:id,name,role'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (AccessoryOrder $order) use ($knownPhoneDigits, $knownEmails): bool {
                $orderDigits = CustomerInstallmentPlan::normalizePhoneDigits($order->customer_phone);
                $orderEmail = mb_strtolower(trim((string) ($order->customer_email ?? '')));

                if ($orderDigits !== '' && in_array($orderDigits, $knownPhoneDigits, true)) {
                    return true;
                }

                return $orderEmail !== '' && in_array($orderEmail, $knownEmails, true);
            })
            ->values();

        $installmentPlans = CustomerInstallmentPlan::query()
            ->with(['product:id,name,price', 'appointment:id,customer_order_code,appointment_date', 'creator:id,name'])
            ->orderByRaw("CASE WHEN status = 'overdue' THEN 0 WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderBy('next_due_date')
            ->orderByDesc('id')
            ->get()
            ->filter(function (CustomerInstallmentPlan $plan) use ($knownPhoneDigits, $knownEmails): bool {
                $planDigits = CustomerInstallmentPlan::normalizePhoneDigits($plan->customer_phone);
                $planEmail = mb_strtolower(trim((string) ($plan->customer_email ?? '')));

                if ($planDigits !== '' && in_array($planDigits, $knownPhoneDigits, true)) {
                    return true;
                }

                return $planEmail !== '' && in_array($planEmail, $knownEmails, true);
            })
            ->values();

        if ($appointments->isEmpty() && $accessoryOrders->isEmpty() && $installmentPlans->isEmpty()) {
            abort(404);
        }

        $appointmentIds = $appointments->pluck('id')->all();
        $orderIds = $accessoryOrders->pluck('id')->all();
        $visitorIds = $appointments->pluck('ai_visitor_id')->filter()->unique()->values()->all();

        $aiSessions = AiChatSession::query()
            ->with(['messages'])
            ->where(function ($query) use ($appointmentIds, $orderIds, $visitorIds): void {
                $hasCondition = false;
                if ($appointmentIds !== []) {
                    $query->orWhere(function ($q) use ($appointmentIds): void {
                        $q->where('linked_source_type', 'appointment')
                            ->whereIn('linked_source_id', $appointmentIds);
                    });
                    $hasCondition = true;
                }
                if ($orderIds !== []) {
                    $query->orWhere(function ($q) use ($orderIds): void {
                        $q->where('linked_source_type', 'accessory_order')
                            ->whereIn('linked_source_id', $orderIds);
                    });
                    $hasCondition = true;
                }
                if ($visitorIds !== []) {
                    $query->orWhereIn('visitor_id', $visitorIds);
                    $hasCondition = true;
                }
                if (! $hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->get();

        $vehicleAppointments = $appointments->filter(static fn (Appointment $a): bool => ! $a->isServiceBooking())->values();
        $serviceBookings = $appointments->filter(static fn (Appointment $a): bool => $a->isServiceBooking())->values();

        $firstAppointment = $appointments->first();
        $firstOrder = $accessoryOrders->first();
        $firstPlan = $installmentPlans->first();

        $resolvedName = trim((string) (
            $firstAppointment?->user?->name
            ?? $firstAppointment?->guest_name
            ?? $firstOrder?->customer_name
            ?? $firstPlan?->customer_name
            ?? 'Khách hàng BMW'
        ));

        $resolvedPhone = trim((string) (
            $firstAppointment?->guest_phone
            ?? $firstOrder?->customer_phone
            ?? $firstPlan?->customer_phone
            ?? ($phoneDigits !== '' ? $phoneDigits : 'N/A')
        ));

        $resolvedEmail = trim((string) (
            $firstAppointment?->guest_email
            ?? $firstAppointment?->user?->email
            ?? $firstOrder?->customer_email
            ?? $firstPlan?->customer_email
            ?? $emailLower
        ));

        $resolvedPhoneDigits = CustomerInstallmentPlan::normalizePhoneDigits($resolvedPhone);

        $primarySales = trim((string) (
            $appointments->first(static fn (Appointment $a): bool => $a->assignedSales !== null)?->assignedSales?->name
            ?? $accessoryOrders->first(static fn (AccessoryOrder $o): bool => $o->assignedSales !== null)?->assignedSales?->name
            ?? 'Chưa phân công'
        ));

        $totalAccessorySpend = (int) $accessoryOrders
            ->filter(static fn (AccessoryOrder $o): bool => (string) $o->status !== AccessoryOrder::STATUS_CANCELLED)
            ->sum('total_amount');

        $openInstallmentsCount = $installmentPlans
            ->filter(static fn (CustomerInstallmentPlan $p): bool => in_array($p->status, [CustomerInstallmentPlan::STATUS_ACTIVE, CustomerInstallmentPlan::STATUS_OVERDUE], true))
            ->count();

        return [
            'profile' => [
                'name' => $resolvedName,
                'phone' => $resolvedPhone !== '' ? $resolvedPhone : 'N/A',
                'phone_digits' => $resolvedPhoneDigits,
                'email' => $resolvedEmail,
                'primary_sales' => $primarySales,
            ],
            'routePhone' => $resolvedPhoneDigits !== '' ? $resolvedPhoneDigits : ($decodedPhone !== '' ? $decodedPhone : 'khach-hang'),
            'routeEmail' => $resolvedEmail,
            'appointments' => $appointments,
            'vehicleAppointments' => $vehicleAppointments,
            'serviceBookings' => $serviceBookings,
            'accessoryOrders' => $accessoryOrders,
            'installmentPlans' => $installmentPlans,
            'aiSessions' => $aiSessions,
            'kpis' => [
                'appointments' => $vehicleAppointments->count(),
                'services' => $serviceBookings->count(),
                'orders' => $accessoryOrders->count(),
                'total_spend' => $totalAccessorySpend,
                'open_installments' => $openInstallmentsCount,
                'ai_sessions' => $aiSessions->count(),
            ],
        ];
    }

    /**
     * Sales can only inspect/manage customers assigned to them (prevents cross-Sales data leak).
     *
     * @param  Collection<int, Appointment>  $appointments
     * @param  Collection<int, AccessoryOrder>  $accessoryOrders
     * @param  Collection<int, CustomerInstallmentPlan>  $installmentPlans
     */
    private function assertSalesCanAccessCustomer(
        Collection $appointments,
        Collection $accessoryOrders,
        Collection $installmentPlans,
    ): void {
        $user = Auth::user();
        if (! $user || ! $user->isSales()) {
            return;
        }

        $userId = (int) $user->id;
        $ownsAppointment = $appointments->contains(static fn (Appointment $a): bool => (int) $a->assigned_to === $userId);
        $ownsOrder = $accessoryOrders->contains(static fn (AccessoryOrder $o): bool => (int) $o->assigned_to === $userId);
        $ownsPlan = $installmentPlans->contains(static fn (CustomerInstallmentPlan $p): bool => (int) $p->created_by === $userId);

        if (! $ownsAppointment && ! $ownsOrder && ! $ownsPlan) {
            abort(403, 'Bạn chỉ có quyền truy cập hồ sơ khách hàng thuộc phạm vi phụ trách của mình.');
        }
    }

    /**
     * @return array<string, string>
     */
    private function customerShowRouteParams(string $routePhone, string $routeEmail): array
    {
        $params = ['phone' => $routePhone];
        if ($routeEmail !== '') {
            $params['email'] = $routeEmail;
        }

        return $params;
    }

    /**
     * @return array{0: array<string, bool>, 1: array<string, bool>}
     */
    private function openInstallmentIdentitySets(): array
    {
        $plans = CustomerInstallmentPlan::query()
            ->open()
            ->get(['customer_email', 'customer_phone']);

        $emails = [];
        $phones = [];

        foreach ($plans as $plan) {
            $email = mb_strtolower(trim((string) ($plan->customer_email ?? '')));
            if ($email !== '') {
                $emails[$email] = true;
            }
            $digits = CustomerInstallmentPlan::normalizePhoneDigits($plan->customer_phone);
            if ($digits !== '') {
                $phones[$digits] = true;
            }
        }

        return [$emails, $phones];
    }

    /**
     * Resolve deduplicated promotion recipients, prioritizing completed appointments
     * and latest interaction when selecting the personalized vehicle model.
     *
     * @return list<array{email: string, name: string, vehicle_name: string}>
     */
    private function resolvePromotionRecipients(?int $productId, bool $onlyCompleted, bool $onlyInstallment = false): array
    {
        [$installmentEmails, $installmentPhones] = $onlyInstallment
            ? $this->openInstallmentIdentitySets()
            : [[], []];

        $fromAppointments = Appointment::query()
            ->with(['user', 'product', 'assignedSales:id,name'])
            ->when($onlyCompleted, fn ($query) => $query->where('status', AppointmentStatus::Completed->value))
            ->when($productId !== null, fn ($query) => $query->where('product_id', $productId))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [AppointmentStatus::Completed->value])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Appointment $appointment) use ($onlyInstallment, $installmentEmails, $installmentPhones): ?array {
                $email = mb_strtolower(trim((string) ($appointment->guest_email ?? $appointment->user?->email ?? '')));

                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                if ($onlyInstallment) {
                    $phoneDigits = CustomerInstallmentPlan::normalizePhoneDigits($appointment->guest_phone);
                    $matchesInstallment = isset($installmentEmails[$email])
                        || ($phoneDigits !== '' && isset($installmentPhones[$phoneDigits]));

                    if (! $matchesInstallment) {
                        return null;
                    }
                }

                return [
                    'email' => $email,
                    'name' => trim((string) ($appointment->user?->name ?? $appointment->guest_name ?? 'Quý khách')),
                    'vehicle_name' => trim((string) (
                        $appointment->product?->name
                        ?? ($appointment->meta_data['customer_car_model'] ?? 'Các dòng xe BMW')
                    )),
                    'advisor_name' => (string) ($appointment->assignedSales?->name ?? ''),
                    '_priority' => $appointment->status === AppointmentStatus::Completed ? 0 : 1,
                    '_date' => $appointment->appointment_date?->getTimestamp() ?? 0,
                    '_id' => (int) $appointment->id,
                ];
            })
            ->filter();

        if ($onlyInstallment) {
            $fromPlans = CustomerInstallmentPlan::query()
                ->with(['product:id,name'])
                ->open()
                ->when($productId !== null, fn ($q) => $q->where('product_id', $productId))
                ->orderByDesc('id')
                ->get()
                ->map(static function (CustomerInstallmentPlan $plan): ?array {
                    $email = mb_strtolower(trim((string) ($plan->customer_email ?? '')));
                    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return null;
                    }

                    return [
                        'email' => $email,
                        'name' => trim((string) $plan->customer_name) ?: 'Quý khách',
                        'vehicle_name' => trim((string) ($plan->product?->name ?? 'Các dòng xe BMW')),
                        'advisor_name' => '',
                        '_priority' => 2,
                        '_date' => $plan->created_at?->getTimestamp() ?? 0,
                        '_id' => (int) $plan->id,
                    ];
                })
                ->filter();

            $fromAppointments = $fromAppointments->concat($fromPlans);
        }

        return $fromAppointments
            ->sort(static function (array $a, array $b): int {
                return [$a['_priority'], $b['_date'], $b['_id']]
                    <=> [$b['_priority'], $a['_date'], $a['_id']];
            })
            ->unique('email')
            ->map(static fn (array $item): array => [
                'email' => $item['email'],
                'name' => $item['name'],
                'vehicle_name' => $item['vehicle_name'],
                'advisor_name' => $item['advisor_name'] ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * Build lightweight priority-sorted audience tuples for live Alpine recipient counting & preview.
     *
     * @param  array<string, bool>  $installmentEmails
     * @param  array<string, bool>  $installmentPhones
     * @return list<array{key: int, name: string, email: string, vehicle: string, product_id: int|null, completed: bool, has_installment: bool}>
     */
    private function buildPromotionAudienceTuples(array $installmentEmails = [], array $installmentPhones = []): array
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
                'appointments.guest_phone',
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
            $phoneDigits = CustomerInstallmentPlan::normalizePhoneDigits((string) ($row->guest_phone ?? ''));
            $hasInstallment = isset($installmentEmails[$email]) || ($phoneDigits !== '' && isset($installmentPhones[$phoneDigits]));
            $signature = $key.'|'.($productId ?? 'none').'|'.($completed ? '1' : '0').'|'.($hasInstallment ? '1' : '0');

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
                'has_installment' => $hasInstallment,
            ];
        }

        // Also include standalone open installment plan emails not present in appointments
        $openPlans = CustomerInstallmentPlan::query()
            ->with('product:id,name')
            ->open()
            ->get();

        foreach ($openPlans as $plan) {
            $email = mb_strtolower(trim((string) ($plan->customer_email ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($emailKeys[$email])) {
                continue;
            }

            $key = ++$nextKey;
            $emailKeys[$email] = $key;
            $tuples[] = [
                'key' => $key,
                'name' => trim((string) $plan->customer_name) ?: 'Quý khách',
                'email' => $email,
                'vehicle' => trim((string) ($plan->product?->name ?? 'Các dòng xe BMW')),
                'product_id' => $plan->product_id,
                'completed' => false,
                'has_installment' => true,
            ];
        }

        return $tuples;
    }
}

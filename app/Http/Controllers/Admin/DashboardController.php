<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Http\Controllers\Controller;
use App\Models\AccessoryOrder;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Concurrency\ShowroomConcurrency;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(ShowroomCacheService $cacheService): View
    {
        $trendStart = now()->subDays(6)->startOfDay();
        $trendEnd = now()->startOfDay();
        $trendStartStr = $trendStart->toDateString();
        $todayStr = today()->toDateString();

        $summary = $cacheService->dashboardSummary(static function () use ($trendStartStr, $todayStr): array {
            return ShowroomConcurrency::run([
                'appointments' => static function () use ($trendStartStr, $todayStr): array {
                    $total = Appointment::query()->count();
                    $today = Appointment::query()->whereDate('appointment_date', $todayStr)->count();
                    $pending = Appointment::query()->where('status', AppointmentStatus::Pending->value)->count();
                    $hot = Appointment::query()->where('lead_priority', Appointment::LEAD_PRIORITY_HOT)->count();

                    $trendRows = Appointment::query()
                        ->selectRaw('DATE(appointment_date) as appointment_day, COUNT(*) as leads_count')
                        ->whereDate('appointment_date', '>=', $trendStartStr)
                        ->groupBy('appointment_day')
                        ->orderBy('appointment_day')
                        ->pluck('leads_count', 'appointment_day')
                        ->all();

                    $typeDistribution = Appointment::query()
                        ->select('type')
                        ->selectRaw('COUNT(*) as leads_count')
                        ->groupBy('type')
                        ->orderByDesc('leads_count')
                        ->get()
                        ->map(static fn (Appointment $appointment): array => [
                            'value' => $appointment->type->value,
                            'label' => $appointment->type->label(),
                            'count' => (int) $appointment->getAttribute('leads_count'),
                        ])
                        ->values()
                        ->all();

                    $topProductRows = Appointment::query()
                        ->select('product_id')
                        ->selectRaw('COUNT(*) as leads_count')
                        ->whereNotNull('product_id')
                        ->groupBy('product_id')
                        ->orderByDesc('leads_count')
                        ->limit(5)
                        ->get()
                        ->map(static fn (Appointment $row): array => [
                            'product_id' => (int) $row->product_id,
                            'leads_count' => (int) $row->getAttribute('leads_count'),
                        ])
                        ->all();

                    $latestIds = Appointment::query()
                        ->latest()
                        ->limit(10)
                        ->pluck('id')
                        ->map(static fn (mixed $id): int => (int) $id)
                        ->all();

                    $tradeInCount = Appointment::query()
                        ->where('type', AppointmentType::TradeIn->value)
                        ->count();

                    $tradeInIds = Appointment::query()
                        ->where('type', AppointmentType::TradeIn->value)
                        ->latest()
                        ->limit(5)
                        ->pluck('id')
                        ->map(static fn (mixed $id): int => (int) $id)
                        ->all();

                    return [
                        'total' => $total,
                        'today' => $today,
                        'pending' => $pending,
                        'hot' => $hot,
                        'trend_rows' => $trendRows,
                        'type_distribution' => $typeDistribution,
                        'top_product_rows' => $topProductRows,
                        'latest_ids' => $latestIds,
                        'trade_in_count' => $tradeInCount,
                        'trade_in_ids' => $tradeInIds,
                    ];
                },
                'services' => static function (): array {
                    $serviceTypes = [
                        AppointmentType::Maintenance->value,
                        AppointmentType::Detailing->value,
                        AppointmentType::CarWash->value,
                    ];

                    $count = Appointment::query()
                        ->whereIn('type', $serviceTypes)
                        ->count();

                    $pendingCount = Appointment::query()
                        ->whereIn('type', $serviceTypes)
                        ->where('status', AppointmentStatus::Pending->value)
                        ->count();

                    $leadIds = Appointment::query()
                        ->whereIn('type', $serviceTypes)
                        ->latest()
                        ->limit(5)
                        ->pluck('id')
                        ->map(static fn (mixed $id): int => (int) $id)
                        ->all();

                    return [
                        'count' => $count,
                        'pending_count' => $pendingCount,
                        'lead_ids' => $leadIds,
                    ];
                },
                'accessory_orders' => static function (): array {
                    $orders = AccessoryOrder::query()
                        ->with('product:id,price')
                        ->get(['id', 'product_id', 'quantity', 'status']);

                    return [
                        'total' => $orders->count(),
                        'pending' => $orders->where('status', AccessoryOrder::STATUS_PENDING)->count(),
                        'revenue' => (int) $orders->where('status', '!=', AccessoryOrder::STATUS_CANCELLED)->sum(
                            static fn (AccessoryOrder $order): int => $order->total_amount
                        ),
                    ];
                },
                'ai_conversations' => static function () use ($todayStr): array {
                    $stats = [
                        'today' => AiChatSession::query()->whereDate('last_seen_at', $todayStr)->count(),
                        'linked' => AiChatSession::query()->whereNotNull('linked_source_type')->count(),
                        'interested' => AiChatSession::query()->whereNotNull('last_intent')->count(),
                        'fallback' => AiChatSession::query()
                            ->whereHas('messages', static fn ($query) => $query->where('role', 'assistant')->where('response_reason', '!=', 'ok'))
                            ->count(),
                    ];

                    $latestIds = AiChatSession::query()
                        ->latest('last_seen_at')
                        ->limit(5)
                        ->pluck('id')
                        ->map(static fn (mixed $id): int => (int) $id)
                        ->all();

                    return [
                        'stats' => $stats,
                        'latest_ids' => $latestIds,
                    ];
                },
            ]);
        });

        $apptSummary = $summary['appointments'] ?? [];
        $serviceSummary = $summary['services'] ?? [];
        $accessorySummary = $summary['accessory_orders'] ?? [];
        $aiSummary = $summary['ai_conversations'] ?? [];

        $trendRows = is_array($apptSummary['trend_rows'] ?? null) ? $apptSummary['trend_rows'] : [];
        $leadTrend = collect(CarbonPeriod::create($trendStart, $trendEnd))
            ->map(static fn (Carbon $date): array => [
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'count' => (int) ($trendRows[$date->toDateString()] ?? 0),
            ]);

        $typeDistribution = collect($apptSummary['type_distribution'] ?? []);

        $topProductIds = collect($apptSummary['top_product_rows'] ?? [])
            ->pluck('product_id')
            ->filter()
            ->all();

        $topProductsById = $topProductIds !== []
            ? Appointment::query()
                ->select('product_id')
                ->selectRaw('COUNT(*) as leads_count')
                ->whereIn('product_id', $topProductIds)
                ->with(['product.category'])
                ->groupBy('product_id')
                ->orderByDesc('leads_count')
                ->get()
            : collect();

        $allHydratedAppointmentIds = array_values(array_unique(array_merge(
            $apptSummary['latest_ids'] ?? [],
            $apptSummary['trade_in_ids'] ?? [],
            $serviceSummary['lead_ids'] ?? [],
        )));

        $hydratedAppointments = $allHydratedAppointmentIds !== []
            ? Appointment::query()
                ->with(['user', 'product.category'])
                ->whereIn('id', $allHydratedAppointmentIds)
                ->get()
                ->keyBy('id')
            : collect();

        $latestAppointments = collect($apptSummary['latest_ids'] ?? [])
            ->map(static fn (int $id): ?Appointment => $hydratedAppointments->get($id))
            ->filter()
            ->values();

        $specialLeadSections = $this->buildSpecialLeadSections(
            tradeInCount: (int) ($apptSummary['trade_in_count'] ?? 0),
            tradeInIds: $apptSummary['trade_in_ids'] ?? [],
            serviceCount: (int) ($serviceSummary['count'] ?? 0),
            serviceIds: $serviceSummary['lead_ids'] ?? [],
            hydratedAppointments: $hydratedAppointments,
        );

        $latestAiIds = $aiSummary['latest_ids'] ?? [];
        $latestAiLeads = $latestAiIds !== []
            ? AiChatSession::query()
                ->whereIn('id', $latestAiIds)
                ->latest('last_seen_at')
                ->get()
            : collect();

        return view('admin.dashboard', [
            'aiConversationStats' => $aiSummary['stats'] ?? [
                'today' => 0,
                'linked' => 0,
                'interested' => 0,
                'fallback' => 0,
            ],
            'accessoryOrderStats' => $accessorySummary,
            'serviceStats' => [
                'count' => (int) ($serviceSummary['count'] ?? 0),
                'pending' => (int) ($serviceSummary['pending_count'] ?? 0),
            ],
            'hotLeadsCount' => (int) ($apptSummary['hot'] ?? 0),
            'latestAppointments' => $latestAppointments,
            'latestAiLeads' => $latestAiLeads,
            'leadTrend' => $leadTrend,
            'maxTrendCount' => max(1, (int) ($leadTrend->max('count') ?? 0)),
            'pendingAppointments' => (int) ($apptSummary['pending'] ?? 0),
            'specialLeadSections' => $specialLeadSections,
            'todayAppointments' => (int) ($apptSummary['today'] ?? 0),
            'topProductLeads' => $topProductsById,
            'totalAppointments' => (int) ($apptSummary['total'] ?? 0),
            'typeDistribution' => $typeDistribution,
            'typeDistributionMax' => max(1, (int) ($typeDistribution->max('count') ?? 0)),
        ]);
    }

    /**
     * @param  list<int>  $tradeInIds
     * @param  list<int>  $serviceIds
     * @param  Collection<int, Appointment>  $hydratedAppointments
     * @return Collection<int, array{key: string, title: string, subtitle: string, count: int, leads: Collection<int, Appointment>, types: array<int, AppointmentType>}>
     */
    private function buildSpecialLeadSections(
        int $tradeInCount,
        array $tradeInIds,
        int $serviceCount,
        array $serviceIds,
        Collection $hydratedAppointments,
    ): Collection {
        return collect([
            [
                'key' => 'trade_in',
                'title' => 'Trade-in',
                'subtitle' => 'Khách có nhu cầu đổi xe cũ lên BMW',
                'types' => [AppointmentType::TradeIn],
                'count' => $tradeInCount,
                'leads' => collect($tradeInIds)
                    ->map(static fn (int $id): ?Appointment => $hydratedAppointments->get($id))
                    ->filter()
                    ->values(),
            ],
            [
                'key' => 'service',
                'title' => 'Service',
                'subtitle' => 'Bảo dưỡng và chăm sóc xe BMW',
                'types' => [
                    AppointmentType::Maintenance,
                    AppointmentType::Detailing,
                    AppointmentType::CarWash,
                ],
                'count' => $serviceCount,
                'leads' => collect($serviceIds)
                    ->map(static fn (int $id): ?Appointment => $hydratedAppointments->get($id))
                    ->filter()
                    ->values(),
            ],
        ]);
    }
}

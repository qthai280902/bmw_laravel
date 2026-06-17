<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Http\Controllers\Controller;
use App\Models\AiChatSession;
use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(): View
    {
        $appointmentQuery = Appointment::query();
        $trendStart = now()->subDays(6)->startOfDay();
        $trendEnd = now()->startOfDay();

        $totalAppointments = (clone $appointmentQuery)->count();
        $todayAppointments = (clone $appointmentQuery)
            ->whereDate('appointment_date', today())
            ->count();
        $pendingAppointments = (clone $appointmentQuery)
            ->where('status', AppointmentStatus::Pending->value)
            ->count();

        $trendRows = (clone $appointmentQuery)
            ->selectRaw('DATE(appointment_date) as appointment_day, COUNT(*) as leads_count')
            ->whereDate('appointment_date', '>=', $trendStart->toDateString())
            ->groupBy('appointment_day')
            ->orderBy('appointment_day')
            ->get()
            ->keyBy(fn (Appointment $appointment): string => (string) $appointment->getAttribute('appointment_day'));

        $leadTrend = collect(CarbonPeriod::create($trendStart, $trendEnd))
            ->map(fn (Carbon $date): array => [
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'count' => (int) ($trendRows->get($date->toDateString())?->getAttribute('leads_count') ?? 0),
            ]);

        $typeDistribution = (clone $appointmentQuery)
            ->select('type')
            ->selectRaw('COUNT(*) as leads_count')
            ->groupBy('type')
            ->orderByDesc('leads_count')
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'value' => $appointment->type->value,
                'label' => $appointment->type->label(),
                'count' => (int) $appointment->getAttribute('leads_count'),
            ]);

        $topProductLeads = Appointment::query()
            ->select('product_id')
            ->selectRaw('COUNT(*) as leads_count')
            ->whereNotNull('product_id')
            ->with(['product.category'])
            ->groupBy('product_id')
            ->orderByDesc('leads_count')
            ->limit(5)
            ->get();

        $latestAppointments = Appointment::query()
            ->with(['user', 'product.category'])
            ->latest()
            ->limit(10)
            ->get();

        $specialLeadSections = $this->specialLeadSections();
        $aiConversationStats = [
            'today' => AiChatSession::query()->whereDate('last_seen_at', today())->count(),
            'linked' => AiChatSession::query()->whereNotNull('linked_source_type')->count(),
            'interested' => AiChatSession::query()->whereNotNull('last_intent')->count(),
            'fallback' => AiChatSession::query()
                ->whereHas('messages', fn ($query) => $query->where('role', 'assistant')->where('response_reason', '!=', 'ok'))
                ->count(),
        ];
        $latestAiLeads = AiChatSession::query()
            ->latest('last_seen_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'aiConversationStats' => $aiConversationStats,
            'latestAppointments' => $latestAppointments,
            'latestAiLeads' => $latestAiLeads,
            'leadTrend' => $leadTrend,
            'maxTrendCount' => max(1, (int) ($leadTrend->max('count') ?? 0)),
            'pendingAppointments' => $pendingAppointments,
            'specialLeadSections' => $specialLeadSections,
            'todayAppointments' => $todayAppointments,
            'topProductLeads' => $topProductLeads,
            'totalAppointments' => $totalAppointments,
            'typeDistribution' => $typeDistribution,
            'typeDistributionMax' => max(1, (int) ($typeDistribution->max('count') ?? 0)),
        ]);
    }

    /**
     * @return Collection<int, array{key: string, title: string, subtitle: string, count: int, leads: Collection<int, Appointment>, types: array<int, AppointmentType>}>
     */
    private function specialLeadSections(): Collection
    {
        $sections = [
            [
                'key' => 'trade_in',
                'title' => 'Trade-in',
                'subtitle' => 'Khach co nhu cau doi xe cu len BMW',
                'types' => [AppointmentType::TradeIn],
            ],
            [
                'key' => 'service',
                'title' => 'Service',
                'subtitle' => 'Bao duong va cham soc xe BMW',
                'types' => [
                    AppointmentType::Maintenance,
                    AppointmentType::Detailing,
                    AppointmentType::CarWash,
                ],
            ],
        ];

        return collect($sections)
            ->map(function (array $section): array {
                $typeValues = array_map(
                    fn (AppointmentType $type): string => $type->value,
                    $section['types'],
                );

                return [
                    'key' => $section['key'],
                    'title' => $section['title'],
                    'subtitle' => $section['subtitle'],
                    'types' => $section['types'],
                    'count' => Appointment::query()
                        ->whereIn('type', $typeValues)
                        ->count(),
                    'leads' => Appointment::query()
                        ->with(['user', 'product.category'])
                        ->whereIn('type', $typeValues)
                        ->latest()
                        ->limit(5)
                        ->get(),
                ];
            });
    }
}

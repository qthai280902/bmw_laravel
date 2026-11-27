<?php

namespace App\Services\Ai;

use App\Ai\Agents\AppointmentLeadClassifierAgent;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\AiManager;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class AppointmentLeadClassifier
{
    public function __construct(
        private GeminiAgentRunner $runner,
    ) {}

    public function classifyAndSave(Appointment $appointment): void
    {
        try {
            $classification = $this->classify($appointment);

            $appointment->forceFill([
                'lead_priority' => $classification['lead_priority'],
                'lead_intent' => $classification['lead_intent'],
                'ai_lead_note' => Str::limit($classification['ai_lead_note'], 250, '...'),
            ])->saveQuietly();
        } catch (Throwable $exception) {
            Log::warning('Appointment lead classification skipped due to exception.', [
                'appointment_id' => $appointment->id,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * @return array{lead_priority: string, lead_intent: string, ai_lead_note: string}
     */
    public function classify(Appointment $appointment): array
    {
        $appointment->loadMissing('product');

        $isFaked = app(AiManager::class)->hasFakeGatewayFor(AppointmentLeadClassifierAgent::class);
        $aiEnabled = (bool) config('showroom_ai.lead_classification_enabled', true);

        if ($aiEnabled) {
            $prompt = $this->buildPrompt($appointment);

            try {
                if ($isFaked) {
                    $response = AppointmentLeadClassifierAgent::make()->prompt($prompt);

                    if ($response instanceof StructuredAgentResponse) {
                        return $this->normalizeResult($response->toArray(), $appointment);
                    }
                }

                $run = $this->runner->run(
                    callback: fn (string $provider, ?string $model): mixed => AppointmentLeadClassifierAgent::make()->prompt(
                        prompt: $prompt,
                        provider: $provider,
                        model: $model,
                        timeout: min(5, max(3, (int) config('showroom_ai.lead_classification_timeout_seconds', 5))),
                    ),
                    operation: 'appointment_lead_classification',
                    maxCandidates: 1,
                    allowFallbackModel: false,
                );

                if (($run['ok'] ?? false) === true && ($run['result'] ?? null) instanceof StructuredAgentResponse) {
                    return $this->normalizeResult($run['result']->toArray(), $appointment);
                }
            } catch (Throwable $exception) {
                Log::warning('Appointment AI lead classification fell back to deterministic rules.', [
                    'appointment_id' => $appointment->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $this->deterministicFallback($appointment);
    }

    private function buildPrompt(Appointment $appointment): string
    {
        $typeLabel = $appointment->type instanceof AppointmentType
            ? $appointment->type->label()
            : (string) $appointment->type;
        $productName = $appointment->product?->name ?? 'Chưa chọn xe cụ thể';
        $appointmentDate = $appointment->appointment_date?->format('d/m/Y H:i') ?? 'Chưa rõ';
        $notes = trim((string) ($appointment->notes ?? 'Không có ghi chú'));
        $showroom = trim((string) ($appointment->showroom ?? 'Showroom chính hãng'));
        $aiChatSummary = trim((string) ($appointment->ai_chat_summary ?? ''));

        $chatContextLine = $aiChatSummary !== ''
            ? "\nTóm tắt lịch sử hỏi Trợ lý AI trước đó: {$aiChatSummary}"
            : '';

        return <<<PROMPT
                Loại lịch hẹn: {$typeLabel}
                Mẫu xe quan tâm: {$productName}
                Ngày hẹn mong muốn: {$appointmentDate}
                Showroom: {$showroom}
                Ghi chú của khách: {$notes}{$chatContextLine}
                PROMPT;
    }

    /**
     * @param  array<string, mixed>  $structured
     * @return array{lead_priority: string, lead_intent: string, ai_lead_note: string}
     */
    private function normalizeResult(array $structured, Appointment $appointment): array
    {
        $fallback = $this->deterministicFallback($appointment);

        $priority = (string) ($structured['lead_priority'] ?? '');
        if (! in_array($priority, [Appointment::LEAD_PRIORITY_HOT, Appointment::LEAD_PRIORITY_WARM, Appointment::LEAD_PRIORITY_NORMAL], true)) {
            $priority = ($structured['urgent'] ?? false) === true || ((int) ($structured['readiness_score'] ?? 0) >= 80)
                ? Appointment::LEAD_PRIORITY_HOT
                : $fallback['lead_priority'];
        }

        $allowedIntents = ['ready_to_buy', 'test_drive_experience', 'price_comparison', 'aftersales_service', 'general_inquiry'];
        $intent = (string) ($structured['lead_intent'] ?? '');
        if (! in_array($intent, $allowedIntents, true)) {
            $intent = $fallback['lead_intent'];
        }

        $note = trim((string) ($structured['ai_lead_note'] ?? ''));

        return [
            'lead_priority' => $priority,
            'lead_intent' => $intent,
            'ai_lead_note' => Str::limit($note !== '' ? $note : $fallback['ai_lead_note'], 250, '...'),
        ];
    }

    /**
     * @return array{lead_priority: string, lead_intent: string, ai_lead_note: string}
     */
    private function deterministicFallback(Appointment $appointment): array
    {
        $type = $appointment->type instanceof AppointmentType
            ? $appointment->type
            : AppointmentType::tryFrom((string) $appointment->type);

        $combinedText = trim(($appointment->notes ?? '').' '.($appointment->ai_chat_summary ?? ''));
        $notesNormalized = Str::lower(Str::ascii($combinedText));
        $hasHotSignal = Str::contains($notesNormalized, [
            'mua ngay',
            'dat coc',
            'chot xe',
            'lay xe',
            'gap',
            'khan cap',
            'tuan nay',
            'trong tuan',
            'thanh toan',
            'giao ngay',
        ]);

        if ($hasHotSignal) {
            return [
                'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
                'lead_intent' => 'ready_to_buy',
                'ai_lead_note' => 'Khách có nhu cầu chốt xe/đặt cọc gấp, ưu tiên liên hệ xác nhận sớm.',
            ];
        }

        return match ($type) {
            AppointmentType::Quote, AppointmentType::TradeIn => [
                'lead_priority' => Appointment::LEAD_PRIORITY_WARM,
                'lead_intent' => 'price_comparison',
                'ai_lead_note' => 'Khách quan tâm báo giá/thu cũ đổi mới, chuẩn bị bảng tính lăn bánh.',
            ],
            AppointmentType::TestDrive, AppointmentType::Viewing => [
                'lead_priority' => Appointment::LEAD_PRIORITY_WARM,
                'lead_intent' => 'test_drive_experience',
                'ai_lead_note' => 'Khách đăng ký trải nghiệm xe trực tiếp tại showroom.',
            ],
            AppointmentType::Maintenance, AppointmentType::Detailing, AppointmentType::CarWash => [
                'lead_priority' => Appointment::LEAD_PRIORITY_NORMAL,
                'lead_intent' => 'aftersales_service',
                'ai_lead_note' => 'Yêu cầu dịch vụ hậu mãi/bảo dưỡng xe BMW.',
            ],
            default => [
                'lead_priority' => Appointment::LEAD_PRIORITY_NORMAL,
                'lead_intent' => 'general_inquiry',
                'ai_lead_note' => 'Khách cần tư vấn thông tin chung về sản phẩm và dịch vụ.',
            ],
        };
    }
}

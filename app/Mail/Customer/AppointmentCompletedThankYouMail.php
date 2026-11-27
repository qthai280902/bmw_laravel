<?php

namespace App\Mail\Customer;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Services\Crm\InteractionTimelineRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AppointmentCompletedThankYouMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public const OUTCOME_PURCHASED = 'purchased';

    public const OUTCOME_VISITED_NO_PURCHASE = 'visited_no_purchase';

    public const OUTCOME_SERVICE_DELIVERED = 'service_delivered';

    public const OUTCOME_SERVICE_INSPECTED_ADVISED = 'service_inspected_advised';

    public const OUTCOME_TRADE_IN_CLOSED = 'trade_in_closed';

    public const OUTCOME_TRADE_IN_APPRAISED_ONLY = 'trade_in_appraised_only';

    public string $queue = 'notifications';

    public function __construct(
        public Appointment $appointment,
        public string $outcome = self::OUTCOME_VISITED_NO_PURCHASE,
        public ?string $voucherCode = null,
        public ?string $voucherExpiresLabel = null,
    ) {}

    public function isPurchased(): bool
    {
        return $this->outcome === self::OUTCOME_PURCHASED;
    }

    public function isServiceOutcome(): bool
    {
        return in_array($this->outcome, [self::OUTCOME_SERVICE_DELIVERED, self::OUTCOME_SERVICE_INSPECTED_ADVISED], true)
            || $this->appointment->isServiceBooking();
    }

    public function isTradeInOutcome(): bool
    {
        return in_array($this->outcome, [self::OUTCOME_TRADE_IN_CLOSED, self::OUTCOME_TRADE_IN_APPRAISED_ONLY], true)
            || $this->appointment->type === AppointmentType::TradeIn;
    }

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);

        $refCode = $this->appointment->publicReferenceCode();
        $vehicleLabel = $this->appointment->product?->name
            ?? ($this->appointment->meta_data['customer_car_model'] ?? 'Phương tiện BMW');

        if ($this->isServiceOutcome()) {
            $serviceLabel = $this->appointment->servicePackageLabel() ?: $this->appointment->typeLabel();
            $subject = "[BMW Service] Xác nhận hoàn tất {$serviceLabel} & Bàn giao xe — Mã {$refCode}";
        } elseif ($this->isTradeInOutcome()) {
            $subject = "[BMW Certified Trade-In] Tổng kết thẩm định phương tiện — Mã {$refCode}";
        } elseif ($this->isPurchased()) {
            $subject = "[BMW Showroom] Chúc mừng Quý khách sở hữu {$vehicleLabel} & Đặc quyền BMW Care 1 tháng — Mã {$refCode}";
        } else {
            $subject = "[BMW Showroom] Thư cảm ơn Quý khách đã tham quan & trải nghiệm {$vehicleLabel} — Mã {$refCode}";
        }

        return new Envelope(
            subject: $subject,
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
            using: [AppointmentConfirmationMail::deduplicateReplyToUsing()],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);
        $advisor = AppointmentConfirmationMail::resolveAdvisorDisplay($this->appointment);
        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->appointment->meta_data);

        $resolvedVoucherCode = null;
        $resolvedExpiresLabel = null;

        if ($this->isPurchased() && ! $this->isServiceOutcome() && ! $this->isTradeInOutcome()) {
            $resolvedVoucherCode = $this->voucherCode ?: ($this->appointment->careVoucherCode() ?: 'BMW-CARE-VIP30D');
            $resolvedExpiresLabel = $this->voucherExpiresLabel
                ?: ($this->appointment->careVoucherExpiresLabel() ?: now('Asia/Ho_Chi_Minh')->addMonth()->format('d/m/Y'));
        }

        $this->voucherCode = $resolvedVoucherCode;
        $this->voucherExpiresLabel = $resolvedExpiresLabel;

        $branchCategory = $this->isServiceOutcome()
            ? 'service'
            : ($this->isTradeInOutcome() ? 'trade_in' : 'sales');

        return new Content(
            view: 'emails.customer.appointment-completed-thank-you',
            text: 'emails.customer.appointment-completed-thank-you-text',
            with: [
                'branchCategory' => $branchCategory,
                'isPurchased' => $this->isPurchased(),
                'isServiceOutcome' => $this->isServiceOutcome(),
                'isTradeInOutcome' => $this->isTradeInOutcome(),
                'outcome' => $this->outcome,
                'advisorName' => $advisor['name'],
                'advisorRole' => $advisor['role'],
                'advisorFormatted' => $advisor['formatted'],
                'voucherCode' => $resolvedVoucherCode,
                'voucherExpiresLabel' => $resolvedExpiresLabel,
                'serviceCompletionNote' => $meta['service_completion_note'] ?? null,
                'serviceActualCost' => isset($meta['service_actual_cost']) && $meta['service_actual_cost'] !== null ? (int) $meta['service_actual_cost'] : null,
                'serviceNextReminderLabel' => $meta['service_next_reminder_label'] ?? null,
                'tradeInNote' => $meta['trade_in_note'] ?? null,
                'inlineImagePath' => $this->appointment->product?->localImagePath(),
                'fallbackBadgePng' => AppointmentConfirmationMail::fallbackBadgePngBytes(),
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'appointment_completed_thank_you',
                'X-BMW-Completion-Outcome' => $this->outcome,
                'X-BMW-Appointment-ID' => (string) $this->appointment->id,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail\Customer;

use App\Models\Appointment;
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

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);

        $refCode = $this->appointment->publicReferenceCode();
        $vehicleLabel = $this->appointment->product?->name ?? 'Dòng xe BMW';

        $subject = $this->isPurchased()
            ? "[BMW Showroom] Chúc mừng Quý khách sở hữu {$vehicleLabel} & Đặc quyền BMW Care 1 tháng — Mã {$refCode}"
            : "[BMW Showroom] Thư cảm ơn Quý khách đã tham quan & trải nghiệm {$vehicleLabel} — Mã {$refCode}";

        return new Envelope(
            subject: $subject,
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);
        $advisor = AppointmentConfirmationMail::resolveAdvisorDisplay($this->appointment);

        $resolvedVoucherCode = null;
        $resolvedExpiresLabel = null;

        if ($this->isPurchased()) {
            $resolvedVoucherCode = $this->voucherCode ?: ($this->appointment->careVoucherCode() ?: 'BMW-CARE-VIP30D');
            $resolvedExpiresLabel = $this->voucherExpiresLabel
                ?: ($this->appointment->careVoucherExpiresLabel() ?: now('Asia/Ho_Chi_Minh')->addMonth()->format('d/m/Y'));
        }

        $this->voucherCode = $resolvedVoucherCode;
        $this->voucherExpiresLabel = $resolvedExpiresLabel;

        return new Content(
            view: 'emails.customer.appointment-completed-thank-you',
            text: 'emails.customer.appointment-completed-thank-you-text',
            with: [
                'isPurchased' => $this->isPurchased(),
                'outcome' => $this->outcome,
                'advisorName' => $advisor['name'],
                'advisorRole' => $advisor['role'],
                'advisorFormatted' => $advisor['formatted'],
                'voucherCode' => $resolvedVoucherCode,
                'voucherExpiresLabel' => $resolvedExpiresLabel,
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

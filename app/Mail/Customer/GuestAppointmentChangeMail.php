<?php

namespace App\Mail\Customer;

use App\Models\Appointment;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class GuestAppointmentChangeMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public const ACTION_RESCHEDULE = 'reschedule';

    public const ACTION_CONFIRM = 'confirm';

    public const ACTION_CANCEL = 'cancel';

    public string $queue = 'notifications';

    public function __construct(
        public Appointment $appointment,
        public string $action = self::ACTION_RESCHEDULE,
        public ?string $previousDateLabel = null,
        public ?string $newDateLabel = null,
        public ?string $previousStatusLabel = null,
        public ?string $newStatusLabel = null,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product.category', 'user']);
        $refCode = $this->appointment->publicReferenceCode();

        $subject = match ($this->action) {
            self::ACTION_CONFIRM => "[BMW Showroom] Xác nhận đến hẹn từ khách hàng — Mã {$refCode}",
            self::ACTION_CANCEL => "[BMW Showroom] Khách hàng đã hủy lịch hẹn — Mã {$refCode}",
            default => "[BMW Showroom] Khách hàng điều chỉnh giờ hẹn — Mã {$refCode}",
        };

        return new Envelope(
            subject: $subject,
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
            tags: ['guest-appointment-change', $this->action],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product.category', 'user']);

        return new Content(
            view: 'emails.customer.guest-appointment-change',
            text: 'emails.customer.guest-appointment-change-text',
            with: [
                'action' => $this->action,
                'actionHeading' => $this->actionHeading(),
                'previousDateLabel' => $this->previousDateLabel,
                'newDateLabel' => $this->newDateLabel ?? $this->appointment->appointment_date?->format('H:i — d/m/Y'),
                'previousStatusLabel' => $this->previousStatusLabel,
                'newStatusLabel' => $this->newStatusLabel ?? $this->appointment->statusLabel(),
                'reason' => $this->reason,
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'guest_appointment_change',
                'X-BMW-Mail-Action' => $this->action,
                'X-BMW-Appointment-ID' => (string) $this->appointment->id,
            ],
        );
    }

    /**
     * @return array<int, Attachment|Attachable>
     */
    public function attachments(): array
    {
        if ($this->action === self::ACTION_CANCEL) {
            return [];
        }

        return [$this->appointment];
    }

    private function actionHeading(): string
    {
        return match ($this->action) {
            self::ACTION_CONFIRM => 'Quý khách đã tự xác nhận tham dự lịch hẹn',
            self::ACTION_CANCEL => 'Quý khách đã hủy lịch hẹn qua liên kết quản lý',
            default => 'Quý khách đã cập nhật ngày/giờ lịch hẹn thành công',
        };
    }
}

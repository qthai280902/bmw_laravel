<?php

namespace App\Mail\Admin;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShowroomDailySummaryMail extends Mailable
{
    use SerializesModels;

    /**
     * @param  array{
     *     date_label: string,
     *     total_appointments: int,
     *     hot_leads: int,
     *     service_bookings: int,
     *     confirmed_or_completed: int,
     *     accessory_orders_count: int,
     *     accessory_orders_value: int
     * }  $summary
     */
    public function __construct(public array $summary) {}

    public function envelope(): Envelope
    {
        $dateLabel = (string) ($this->summary['date_label'] ?? now()->format('d/m/Y'));

        return new Envelope(
            subject: "[BMW Showroom] Báo cáo tổng kết vận hành ngày {$dateLabel}",
            tags: ['daily-summary'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.daily-summary',
            text: 'emails.admin.daily-summary-text',
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

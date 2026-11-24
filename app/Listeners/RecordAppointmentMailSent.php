<?php

namespace App\Listeners;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\CustomerEmailLog;
use App\Models\CustomerInstallmentPlan;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Crm\InteractionTimelineRecorder;
use App\Services\Mail\GmailQuotaGuard;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use ReflectionProperty;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mime\Address;
use Throwable;

class RecordAppointmentMailSent
{
    public function handle(MessageSent $event): void
    {
        try {
            app(GmailQuotaGuard::class)->recordSentMail();
        } catch (Throwable) {
            // Do not allow quota counter cache hiccup to interrupt mail handling
        }

        $this->recordAppointmentConfirmationTimestamp($event);
        $this->recordCustomerTransactionalEmailLog($event);
        $this->notifyIfFailoverFellBackToLog($event);
    }

    private function recordAppointmentConfirmationTimestamp(MessageSent $event): void
    {
        $originalMessage = $event->sent->getOriginalMessage();

        if (! method_exists($originalMessage, 'getHeaders')) {
            return;
        }

        $headers = $originalMessage->getHeaders();
        $mailKind = $headers->get('X-BMW-Mail-Kind')?->getBodyAsString();
        $mailMode = $headers->get('X-BMW-Mail-Mode')?->getBodyAsString();
        $appointmentId = (int) ($headers->get('X-BMW-Appointment-ID')?->getBodyAsString() ?? 0);

        if ($mailKind !== 'appointment_confirmation' || $appointmentId <= 0) {
            return;
        }

        if ($mailMode === 'admin_confirmed') {
            $appointment = Appointment::query()->find($appointmentId);
            if ($appointment) {
                $meta = InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);
                $meta['official_confirmation_sent_at'] = now()->toIso8601String();
                $appointment->forceFill([
                    'confirmation_email_sent_at' => $appointment->confirmation_email_sent_at ?? now(),
                    'meta_data' => $meta,
                ])->saveQuietly();
            }

            return;
        }

        $appointment = Appointment::query()->find($appointmentId);
        if (! $appointment) {
            return;
        }

        $meta = InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);
        $meta = InteractionTimelineRecorder::appendToMeta(
            meta: $meta,
            actorType: InteractionTimelineRecorder::ACTOR_SYSTEM,
            actorLabel: 'Hệ thống tự động',
            title: 'Gửi thư tiếp nhận ban đầu',
            detail: 'Hệ thống tự động gửi email xác nhận đã tiếp nhận yêu cầu kèm liên kết quản lý Signed URL.',
            channel: 'Email',
        );

        $appointment->forceFill([
            'confirmation_email_sent_at' => now(),
            'meta_data' => $meta,
        ])->saveQuietly();
    }

    private function recordCustomerTransactionalEmailLog(MessageSent $event): void
    {
        try {
            $originalMessage = $event->sent->getOriginalMessage();

            if (! method_exists($originalMessage, 'getHeaders')) {
                return;
            }

            $headers = $originalMessage->getHeaders();
            $mailKind = trim((string) ($headers->get('X-BMW-Mail-Kind')?->getBodyAsString() ?? ''));

            // Skip non-customer mails and skip templated/campaign mails already logged at dispatch time
            if ($mailKind === '' || in_array($mailKind, ['templated_customer_email', 'promotion_campaign'], true)) {
                return;
            }

            $mailType = match ($mailKind) {
                'appointment_confirmation' => CustomerEmailLog::TYPE_BOOKING_CONFIRMATION,
                'appointment_completed_thank_you' => CustomerEmailLog::TYPE_POST_VISIT_THANK_YOU,
                'guest_appointment_change',
                'appointment_reminder',
                'post_test_drive_followup',
                'accessory_order_confirmation',
                'accessory_order_status' => CustomerEmailLog::TYPE_OTHER,
                default => null,
            };

            if ($mailType === null) {
                return;
            }

            $recipientEmail = '';
            if (method_exists($originalMessage, 'getTo')) {
                $toAddresses = $originalMessage->getTo();
                if (is_array($toAddresses) && isset($toAddresses[0]) && $toAddresses[0] instanceof Address) {
                    $recipientEmail = mb_strtolower(trim($toAddresses[0]->getAddress()));
                }
            }

            if ($recipientEmail === '' || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $subject = 'BMW Showroom Email';
            if (method_exists($originalMessage, 'getSubject')) {
                $rawSubject = trim((string) $originalMessage->getSubject());
                if ($rawSubject !== '') {
                    $subject = $rawSubject;
                }
            }

            $rawBody = '';
            if (method_exists($originalMessage, 'getTextBody') && is_string($originalMessage->getTextBody())) {
                $rawBody = $originalMessage->getTextBody();
            } elseif (method_exists($originalMessage, 'getHtmlBody') && is_string($originalMessage->getHtmlBody())) {
                $rawBody = strip_tags($originalMessage->getHtmlBody());
            }

            $bodyExcerpt = Str::limit(trim((string) preg_replace('/\s+/u', ' ', $rawBody)), 260);

            $appointmentId = (int) ($headers->get('X-BMW-Appointment-ID')?->getBodyAsString() ?? 0);
            $orderId = (int) ($headers->get('X-BMW-Order-ID')?->getBodyAsString() ?? 0);

            $customerPhone = null;
            $customerName = null;

            if ($appointmentId > 0) {
                $appointment = Appointment::query()->with('user:id,name,email,phone')->find($appointmentId);
                if ($appointment) {
                    $digits = CustomerInstallmentPlan::normalizePhoneDigits($appointment->guest_phone ?: $appointment->user?->phone);
                    $customerPhone = $digits !== '' ? $digits : null;
                    $customerName = $appointment->customer_name;
                }
            } elseif ($orderId > 0) {
                $order = AccessoryOrder::query()->find($orderId);
                if ($order) {
                    $digits = CustomerInstallmentPlan::normalizePhoneDigits($order->customer_phone);
                    $customerPhone = $digits !== '' ? $digits : null;
                    $customerName = $order->customer_name;
                }
            }

            CustomerEmailLog::query()->create([
                'customer_phone' => $customerPhone,
                'customer_email' => $recipientEmail,
                'customer_name' => $customerName,
                'mail_type' => $mailType,
                'template_code' => null,
                'subject' => Str::limit($subject, 250, ''),
                'body_excerpt' => $bodyExcerpt !== '' ? $bodyExcerpt : null,
                'sent_by' => null,
                'sent_at' => now(),
                'meta_data' => [
                    'mail_kind' => $mailKind,
                    'appointment_id' => $appointmentId > 0 ? $appointmentId : null,
                    'order_id' => $orderId > 0 ? $orderId : null,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed recording customer transactional email log.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyIfFailoverFellBackToLog(MessageSent $event): void
    {
        if (config('mail.default') !== 'failover') {
            return;
        }

        if (! $this->didFallbackToLogTransport($event)) {
            return;
        }

        try {
            $originalMessage = $event->sent->getOriginalMessage();
            $recipient = 'unknown';
            $subject = 'BMW Showroom Email';

            if (method_exists($originalMessage, 'getTo')) {
                $toAddresses = $originalMessage->getTo();
                if (is_array($toAddresses) && isset($toAddresses[0]) && $toAddresses[0] instanceof Address) {
                    $recipient = $toAddresses[0]->getAddress();
                }
            }

            if (method_exists($originalMessage, 'getSubject')) {
                $rawSubject = trim((string) $originalMessage->getSubject());
                if ($rawSubject !== '') {
                    $subject = $rawSubject;
                }
            }

            app(AdminNotificationDispatcher::class)->notifyMailDeliveryFailure(
                recipientEmail: $recipient,
                mailContext: "Failover rơi sang kênh log: {$subject}",
                errorMessage: 'Kênh SMTP chính gặp sự cố hoặc vượt hạn ngạch; email đã được chuyển sang kênh dự phòng log.',
            );
        } catch (Throwable $exception) {
            Log::warning('Failed dispatching mail failover fallback notification.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function didFallbackToLogTransport(MessageSent $event): bool
    {
        $explicitFallback = $event->data['failover_fallback_transport'] ?? null;
        if (is_string($explicitFallback) && mb_strtolower(trim($explicitFallback)) === 'log') {
            return true;
        }

        $root = Mail::getFacadeRoot();
        if (! $root instanceof MailManager) {
            return false;
        }

        try {
            $mailer = $root->mailer('failover');
            $transport = $mailer->getSymfonyTransport();

            if (! $transport instanceof FailoverTransport) {
                return false;
            }

            $property = new ReflectionProperty(FailoverTransport::class, 'currentTransport');
            $currentTransport = $property->getValue($transport);

            return $currentTransport instanceof LogTransport;
        } catch (Throwable) {
            return false;
        }
    }
}

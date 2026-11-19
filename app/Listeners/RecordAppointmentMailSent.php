<?php

namespace App\Listeners;

use App\Models\Appointment;
use App\Services\Admin\AdminNotificationDispatcher;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ReflectionProperty;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mime\Address;
use Throwable;

class RecordAppointmentMailSent
{
    public function handle(MessageSent $event): void
    {
        $this->recordAppointmentConfirmationTimestamp($event);
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
        $appointmentId = (int) ($headers->get('X-BMW-Appointment-ID')?->getBodyAsString() ?? 0);

        if ($mailKind !== 'appointment_confirmation' || $appointmentId <= 0) {
            return;
        }

        Appointment::query()
            ->whereKey($appointmentId)
            ->update([
                'confirmation_email_sent_at' => now(),
            ]);
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

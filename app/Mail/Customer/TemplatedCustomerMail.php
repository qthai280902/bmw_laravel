<?php

namespace App\Mail\Customer;

use App\Models\EmailTemplate;
use App\Services\Mail\EmailTemplateRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TemplatedCustomerMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    /**
     * @param  array<string, string>  $variables
     */
    public function __construct(
        public EmailTemplate $template,
        public array $variables = [],
        public ?string $subjectOverride = null,
        public ?string $bodyOverride = null,
    ) {}

    public function envelope(): Envelope
    {
        $rendered = app(EmailTemplateRenderer::class)->renderTemplate(
            $this->template,
            $this->variables,
            $this->subjectOverride,
            $this->bodyOverride,
        );

        return new Envelope(
            subject: $rendered['subject'],
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
            using: [AppointmentConfirmationMail::deduplicateReplyToUsing()],
        );
    }

    public function content(): Content
    {
        $rendered = app(EmailTemplateRenderer::class)->renderTemplate(
            $this->template,
            $this->variables,
            $this->subjectOverride,
            $this->bodyOverride,
        );

        return new Content(
            view: 'emails.customer.templated',
            text: 'emails.customer.templated-text',
            with: [
                'rendered' => $rendered,
                'templateCode' => $this->template->code,
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'templated_customer_email',
                'X-BMW-Template-Code' => (string) $this->template->code,
            ],
        );
    }
}

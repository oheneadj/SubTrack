<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\MailTemplate;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Subscription $subscription,
        public ?string $paymentUrl = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $template = MailTemplate::getBySlug('subscription-reminder');
        $name = $this->subscription->domain_name ?: ($this->subscription->service_type?->label() ?? 'Service');

        $subject = $template ? $template->render([
            '{client_name}' => $this->subscription->project?->client?->name ?? 'Client',
            '{project_name}' => $this->subscription->project?->project_name ?? 'Project',
            '{service_name}' => $name,
            '{provider}' => $this->subscription->provider?->name ?? 'Provider',
            '{expiry_date}' => $this->subscription->expiry_date->format('F d, Y'),
            '{days_remaining}' => $this->subscription->days_until_expiry,
            '{app_name}' => config('app.name'),
            '{payment_url}' => $this->paymentUrl ?? '',
        ])->subject : "[Notification] Service Renewal Reminder: {$name}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = MailTemplate::getBySlug('subscription-reminder');
        $name = $this->subscription->domain_name ?: ($this->subscription->service_type?->label() ?? 'Service');

        $body = $template ? $template->render([
            '{client_name}' => $this->subscription->project?->client?->name ?? 'Client',
            '{project_name}' => $this->subscription->project?->project_name ?? 'Project',
            '{service_name}' => $name,
            '{provider}' => $this->subscription->provider?->name ?? 'Provider',
            '{expiry_date}' => $this->subscription->expiry_date->format('F d, Y'),
            '{days_remaining}' => $this->subscription->days_until_expiry,
            '{app_name}' => config('app.name'),
            '{payment_url}' => $this->paymentUrl ?? '',
        ])->body : null;

        return new Content(
            view: 'emails.subscription-reminder',
            with: [
                'body' => $body,
                'paymentUrl' => $this->paymentUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

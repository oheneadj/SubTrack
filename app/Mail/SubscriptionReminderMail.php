<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\MailTemplate;

class SubscriptionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Subscription $subscription,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $template = MailTemplate::getBySlug('subscription-reminder');
        $name = $this->subscription->domain_name ?? $this->subscription->service_type->label();
        
        $subject = $template ? $template->render([
            '{client_name}'    => $this->subscription->project->client->name,
            '{project_name}'   => $this->subscription->project->name,
            '{service_name}'   => $name,
            '{provider}'       => $this->subscription->provider,
            '{expiry_date}'    => $this->subscription->expiry_date->format('F d, Y'),
            '{days_remaining}' => $this->subscription->days_until_expiry,
            '{app_name}'       => config('app.name'),
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
        $name = $this->subscription->domain_name ?? $this->subscription->service_type->label();

        $body = $template ? $template->render([
            '{client_name}'    => $this->subscription->project->client->name,
            '{project_name}'   => $this->subscription->project->name,
            '{service_name}'   => $name,
            '{provider}'       => $this->subscription->provider,
            '{expiry_date}'    => $this->subscription->expiry_date->format('F d, Y'),
            '{days_remaining}' => $this->subscription->days_until_expiry,
            '{app_name}'       => config('app.name'),
        ])->body : null;

        return new Content(
            view: 'emails.subscription-reminder',
            with: [
                'body' => $body,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

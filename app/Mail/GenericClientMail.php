<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericClientMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Client $client,
        public string $customSubject,
        public string $customBody,
        public ?\App\Models\Subscription $subscription = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replaceVariables($this->customSubject),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.generic-client-mail',
            with: [
                'body'   => $this->replaceVariables($this->customBody),
                'client' => $this->client,
            ],
        );
    }

    /**
     * Replace variables in the content
     */
    protected function replaceVariables(string $content): string
    {
        $vars = [
            '{client_name}'             => $this->client->name,
            '{company_name}'            => Setting::get('business_name', config('app.name')),
            '{company_email}'           => Setting::get('business_email', ''),
            '{company_contact_details}' => Setting::get('business_phone', '') . ' ' . Setting::get('business_website', ''),
            '{app_name}'                => config('app.name'),
        ];

        if ($this->subscription) {
            $vars['{project_name}'] = $this->subscription->project->project_name ?? '';
            $vars['{service_name}'] = $this->subscription->domain_name ?: ($this->subscription->service_type->label() ?? '');
            $vars['{provider}'] = $this->subscription->provider?->name ?? '';
            $vars['{expiry_date}'] = $this->subscription->expiry_date?->format('F j, Y') ?? '';
            $vars['{days_remaining}'] = $this->subscription->days_until_expiry ?? '';
        }

        foreach ($vars as $key => $value) {
            $content = str_replace($key, (string) $value, $content);
        }

        return $content;
    }
}

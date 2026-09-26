<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Client;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenericClientMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Client $client,
        public string $customSubject,
        public string $customBody,
        public ?Subscription $subscription = null,
    ) {
        $this->onQueue('emails');
    }

    /**
     * Handle a job failure after all retries have been exhausted.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Direct mailer failed to deliver to client after retries', [
            'client_id' => $this->client->id,
            'client_email' => $this->client->email,
            'error' => $exception->getMessage(),
        ]);
    }

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
                'body' => $this->replaceVariables($this->customBody),
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
            '{client_name}' => $this->client->name,
            '{company_name}' => Setting::get('business_name', config('app.name')),
            '{company_email}' => Setting::get('business_email', ''),
            '{company_contact_details}' => Setting::get('business_phone', '').' '.Setting::get('business_website', ''),
            '{app_name}' => config('app.name'),
        ];

        if ($this->subscription) {
            $vars['{project_name}'] = $this->subscription->project?->project_name ?? '';
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

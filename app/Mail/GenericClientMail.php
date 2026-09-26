<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Client;
use App\Models\Subscription;
use App\Services\ClientMailPersonalizer;
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
            subject: $this->rendered()['subject'],
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
                'body' => $this->rendered()['body'],
                'client' => $this->client,
            ],
        );
    }

    /**
     * @return array{subject: string, body: string}
     */
    protected function rendered(): array
    {
        return app(ClientMailPersonalizer::class)->render(
            $this->client,
            $this->customSubject,
            $this->customBody,
            $this->subscription,
        );
    }
}

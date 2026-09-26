<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\TracksEmailDelivery;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends a magic login link to a client so they can access the client portal.
 * The link is single-use and expires after 24 hours.
 */
class ClientMagicLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TracksEmailDelivery;

    // The client is actively waiting on this link — fail fast and retry quickly.
    public int $tries = 2;

    public int $timeout = 15;

    /** @var array<int, int> */
    public array $backoff = [5, 15];

    public function __construct(
        public readonly Client $client,
        public readonly string $loginUrl,
        public ?int $emailLogId = null,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your login link for '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-magic-link',
            with: [
                'client' => $this->client,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}

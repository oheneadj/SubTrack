<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\TracksEmailDelivery;
use App\Models\MailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TracksEmailDelivery;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $userName,
        public string $userEmail,
        public string $plainPassword,
        public string $loginUrl,
        public ?int $emailLogId = null,
    ) {
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $template = MailTemplate::getBySlug('user-invite');
        $subject = $template ? $template->render([
            '{user_name}' => $this->userName,
            '{user_email}' => $this->userEmail,
            '{password}' => $this->plainPassword,
            '{login_url}' => $this->loginUrl,
            '{app_name}' => config('app.name'),
        ])->subject : "You've been invited to ".config('app.name');

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = MailTemplate::getBySlug('user-invite');
        $body = $template ? $template->render([
            '{user_name}' => $this->userName,
            '{user_email}' => $this->userEmail,
            '{password}' => $this->plainPassword,
            '{login_url}' => $this->loginUrl,
            '{app_name}' => config('app.name'),
        ])->body : null;

        return new Content(
            view: 'emails.user-invite',
            with: [
                'body' => $body,
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

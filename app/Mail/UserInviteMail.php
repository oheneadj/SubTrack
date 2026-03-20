<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\MailTemplate;

class UserInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $userName,
        public string $userEmail,
        public string $plainPassword,
        public string $loginUrl,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $template = MailTemplate::getBySlug('user-invite');
        $subject = $template ? $template->render([
            '{user_name}'  => $this->userName,
            '{user_email}' => $this->userEmail,
            '{password}'   => $this->plainPassword,
            '{login_url}'  => $this->loginUrl,
            '{app_name}'   => config('app.name'),
        ])->subject : "You've been invited to " . config('app.name');

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
            '{user_name}'  => $this->userName,
            '{user_email}' => $this->userEmail,
            '{password}'   => $this->plainPassword,
            '{login_url}'  => $this->loginUrl,
            '{app_name}'   => config('app.name'),
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
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

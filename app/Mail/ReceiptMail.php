<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Emails a generated receipt PDF to the client it was issued for. */
class ReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Receipt $receipt,
    ) {}

    /** Get the message envelope. */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Receipt: '.$this->receipt->receipt_number,
        );
    }

    /** Get the message content definition. */
    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt-mail',
            with: ['receipt' => $this->receipt],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->receipt->pdf_path && Storage::exists('public/'.$this->receipt->pdf_path)) {
            $attachments[] = Attachment::fromStorage('public/'.$this->receipt->pdf_path)
                ->as($this->receipt->receipt_number.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}

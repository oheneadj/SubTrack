<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use App\Models\MailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice,
        public ?string $paymentUrl = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $template = MailTemplate::getBySlug('invoice-mail');

        $subject = $template ? $template->render([
            '{client_name}' => optional($this->invoice->client)->name ?? 'Client',
            '{project_name}' => optional($this->invoice->project)->project_name ?? 'Project',
            '{invoice_number}' => $this->invoice->invoice_number,
            '{due_date}' => optional($this->invoice->due_date)?->format('F d, Y') ?? 'N/A',
            '{total_amount}' => $this->invoice->formatted_total_amount,
            '{app_name}' => config('app.name'),
        ])->subject : 'New Invoice: '.$this->invoice->invoice_number;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = MailTemplate::getBySlug('invoice-mail');

        $body = $template ? $template->render([
            '{client_name}' => optional($this->invoice->client)->name ?? 'Client',
            '{project_name}' => optional($this->invoice->project)->project_name ?? 'Project',
            '{invoice_number}' => $this->invoice->invoice_number,
            '{due_date}' => optional($this->invoice->due_date)?->format('F d, Y') ?? 'N/A',
            '{total_amount}' => $this->invoice->formatted_total_amount,
            '{app_name}' => config('app.name'),
            '{payment_url}' => $this->paymentUrl ?? '',
        ])->body : null;

        return new Content(
            view: 'emails.invoice-mail',
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
        $attachments = [];

        if ($this->invoice->pdf_path && Storage::exists('public/'.$this->invoice->pdf_path)) {
            $attachments[] = Attachment::fromStorage('public/'.$this->invoice->pdf_path)
                ->as($this->invoice->invoice_number.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}

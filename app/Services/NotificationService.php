<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityEventType;
use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Mail\SubscriptionReminderMail;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class NotificationService
{
    public function __construct(
        protected ActivityLogService $activityLog,
        protected InvoiceNumberService $invoiceNumbers,
    ) {}

    public function sendExpiryReminder(Subscription $subscription): void
    {
        $subscription->load(['client', 'project.client', 'renewals' => fn ($q) => $q->latest()->limit(1)]);
        $client = $subscription->effective_client;

        // Find or create an invoice to attach a payment link to the reminder.
        $invoice = $this->resolveInvoiceForReminder($subscription);
        $paymentUrl = $invoice ? URL::signedRoute('invoice.pay', ['invoice' => $invoice->ulid]) : null;

        Mail::to($client->email)->send(
            new SubscriptionReminderMail($subscription, $paymentUrl)
        );

        $this->activityLog->logMail(
            'expiry_reminder',
            $client->email,
            "Sent expiry reminder for {$subscription->domain_name} to {$client->email}",
            ['subscription_id' => $subscription->id]
        );

        DashboardActivityLog::record(
            ActivityEventType::ReminderSent,
            "Reminder sent to {$client->name} ({$subscription->service_type->value}, {$subscription->days_until_expiry} days)",
            $client->id,
            ['subscription_id' => $subscription->id, 'days_left' => $subscription->days_until_expiry]
        );
    }

    public function sendInvoice(Invoice $invoice): void
    {
        $invoice->load(['client', 'project']);

        $paymentUrl = URL::signedRoute('invoice.pay', ['invoice' => $invoice->ulid]);

        Mail::to($invoice->client->email)->send(
            new InvoiceMail($invoice, $paymentUrl)
        );

        $invoice->update(['status' => InvoiceStatus::Sent]);

        $this->activityLog->logMail(
            'invoice',
            $invoice->client->email,
            "Sent invoice {$invoice->invoice_number} to {$invoice->client->email}",
            ['invoice_id' => $invoice->id]
        );

        DashboardActivityLog::record(
            ActivityEventType::InvoiceSent,
            "Invoice {$invoice->invoice_number} sent to {$invoice->client->name}",
            $invoice->client_id,
            ['invoice_id' => $invoice->id, 'total' => $invoice->total_amount]
        );
    }

    /**
     * Find a pending invoice linked to the subscription's latest renewal,
     * or auto-create a draft invoice if none exists.
     *
     * Returns null if the subscription has no cost data to invoice.
     */
    private function resolveInvoiceForReminder(Subscription $subscription): ?Invoice
    {
        $client = $subscription->effective_client;
        if (! $client) {
            return null;
        }

        // Check if the latest renewal already has an invoice.
        $renewal = $subscription->renewals->first();
        if ($renewal && $renewal->invoice_id) {
            return Invoice::find($renewal->invoice_id);
        }

        // No invoice yet — auto-create a draft invoice for the renewal amount.
        $costCents = $subscription->client_renewal_cost_usd;
        if ($costCents <= 0) {
            return null;
        }

        $serviceName = $subscription->domain_name ?: $subscription->service_type->label();
        $now = CarbonImmutable::now();

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'project_id' => $subscription->project_id,
            'invoice_number' => $this->invoiceNumbers->generate(),
            'issued_date' => $now->toDateString(),
            'due_date' => $subscription->expiry_date->toDateString(),
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal' => $costCents,
            'total_amount' => $costCents,
            'status' => InvoiceStatus::Draft,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'renewal_id' => $renewal?->id,
            'description' => "Renewal: {$serviceName}",
            'period' => "Expiring {$subscription->expiry_date->format('M d, Y')}",
            'quantity' => 1,
            'unit_price' => $costCents,
            'total' => $costCents,
        ]);

        // Link the renewal to this invoice if one exists.
        if ($renewal) {
            $renewal->update(['invoice_id' => $invoice->id]);
        }

        return $invoice;
    }
}

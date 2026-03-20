<?php

namespace App\Services;

use App\Enums\ActivityEventType;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Mail\SubscriptionReminderMail;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        protected ActivityLogService $activityLog,
    ) {}

    public function sendExpiryReminder(Subscription $subscription): void
    {
        $subscription->load('project.client');
        $client = $subscription->project->client;

        Mail::to($client->email)->send(
            new SubscriptionReminderMail($subscription)
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

        Mail::to($invoice->client->email)->send(
            new InvoiceMail($invoice)
        );

        $invoice->update(['status' => 'Sent']);

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
}

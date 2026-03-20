<?php

namespace App\Observers;

use App\Enums\ActivityEventType;
use App\Enums\InvoiceStatus;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;

class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status')) {
            if ($invoice->status === InvoiceStatus::Paid) {
                DashboardActivityLog::record(
                    ActivityEventType::InvoicePaid,
                    "Invoice {$invoice->invoice_number} marked as paid",
                    $invoice->client_id,
                    ['invoice_id' => $invoice->id, 'total' => $invoice->total_amount]
                );
            }
            if ($invoice->status === InvoiceStatus::Overdue) {
                DashboardActivityLog::record(
                    ActivityEventType::InvoiceOverdue,
                    "Invoice {$invoice->invoice_number} is overdue",
                    $invoice->client_id,
                    ['invoice_id' => $invoice->id]
                );
            }
        }
    }
}

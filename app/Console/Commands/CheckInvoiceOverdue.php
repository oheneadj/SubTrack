<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Flags Sent invoices as Overdue once their due date has passed. Nothing
 * else in the app ever performs this transition — invoice status only
 * otherwise changes in response to a payment (recalculatePaymentStatus())
 * — so without this command a Sent invoice stays labeled Sent forever,
 * no matter how late it is, and every dashboard figure and stat card that
 * filters on InvoiceStatus::Overdue silently never matches anything.
 *
 * Deliberately scoped to Sent only, not Partially Paid: a partially paid
 * invoice past due date is still genuinely late, but collapsing it into
 * Overdue would destroy the "some money already came in" information the
 * PartiallyPaid status communicates — the two aren't just alternate
 * labels for the same thing.
 */
class CheckInvoiceOverdue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subtrack:check-invoice-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flag Sent invoices past their due date as Overdue';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $overdueInvoices = Invoice::where('status', InvoiceStatus::Sent)
            ->whereDate('due_date', '<', now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            // Per-model update (not a mass update()) so InvoiceObserver's
            // activity-log entry for the transition actually fires.
            $invoice->update(['status' => InvoiceStatus::Overdue]);
            $this->warn("Invoice {$invoice->invoice_number} is now Overdue (due {$invoice->due_date->format('M d, Y')}).");
        }

        $this->info("Done! Flagged {$overdueInvoices->count()} invoice(s) as Overdue.");
    }
}

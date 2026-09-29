<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Renewal;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Revenue has two sources in this app: paid Invoices, and subscription
 * Renewals paid for directly without ever being invoiced (see
 * Renewal::whereNull('invoice_id')). Every figure here counts both, or a
 * client who only ever pays via direct renewals would look like they've
 * never paid anything.
 */
class RevenueService
{
    /**
     * All-time total of every dollar actually received, from both sources.
     */
    public function totalRevenue(): float
    {
        return $this->invoiceRevenueQuery()->sum('amount_paid') / 100
            + $this->directRenewalRevenueQuery()->sum('client_cost_usd') / 100;
    }

    public function lastSixMonths(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'total' => $this->revenueForMonth($date->year, $date->month),
                'year' => $date->year,
                'month' => $date->month,
            ];
        });

        return $months->toArray();
    }

    public function currentMonthTotal(): float
    {
        return $this->revenueForMonth(now()->year, now()->month);
    }

    public function previousMonthTotal(): float
    {
        $lastMonth = now()->subMonth();

        return $this->revenueForMonth($lastMonth->year, $lastMonth->month);
    }

    public function monthOverMonthChange(): array
    {
        $current = $this->currentMonthTotal();
        $previous = $this->previousMonthTotal();
        $diff = $current - $previous;
        $pct = $previous > 0 ? round(($diff / $previous) * 100) : 0;

        return [
            'current' => $current,
            'previous' => $previous,
            'diff' => $diff,
            'percentage' => $pct,
            'direction' => $diff >= 0 ? 'up' : 'down',
        ];
    }

    public function comparisonData(int $months = 12): array
    {
        // Estimated monthly provider costs, held constant across every
        // month in the range — computed once, not per iteration.
        $monthlyExpenses = $this->estimatedMonthlyProviderCosts();

        $data = collect(range($months - 1, 0))->map(function ($monthsAgo) use ($monthlyExpenses) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M Y'),
                'revenue' => $this->revenueForMonth($date->year, $date->month),
                'expenses' => $monthlyExpenses,
            ];
        });

        return $data->toArray();
    }

    /**
     * Money actually owed by clients right now — the remaining balance on
     * every invoice that's been sent but isn't fully paid. Sums balance_due
     * (total_amount - amount_paid), not total_amount: a Partially Paid
     * invoice still owes something, just not the full total, and that
     * remainder was previously left out of this figure entirely.
     */
    public function outstandingRevenue(): float
    {
        return Invoice::whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue, InvoiceStatus::PartiallyPaid])
            ->get(['total_amount', 'amount_paid'])
            ->sum(fn (Invoice $invoice) => $invoice->balance_due) / 100;
    }

    /**
     * Estimated Monthly Recurring Revenue — every active subscription's
     * client-facing renewal cost (with markup), annualized then divided by
     * 12. An estimate of what a "typical" month brings in if everything
     * renews on schedule, not money actually received.
     */
    public function estimatedMonthlyRecurringRevenue(): float
    {
        $annualRecurring = Subscription::where('status', SubscriptionStatus::Active)
            ->get()
            ->sum('client_renewal_cost_usd') / 100;

        return $annualRecurring / 12;
    }

    /**
     * Total actually paid to providers for renewals — only renewals that
     * have actually been paid for (Paid/Renewed), not ones still Pending
     * payment. A renewal is created Pending the moment "Start Renewal"
     * raises its invoice (see PrepareRenewalAction), before any payment
     * has been collected, so counting every renewal here would inflate
     * costs for money that hasn't come in yet.
     */
    public function totalProviderCosts(): float
    {
        return Renewal::whereIn('payment_status', [PaymentStatus::Paid, PaymentStatus::Renewed])
            ->sum('provider_cost_usd') / 100;
    }

    /**
     * Estimated monthly provider cost, from active subscriptions'
     * provider-facing renewal_cost_usd (no markup — this is what *we*
     * pay, not what the client pays) annualized then divided by 12. An
     * "Expected Monthly Cost" baseline, not literal expense tracking.
     */
    private function estimatedMonthlyProviderCosts(): float
    {
        $annual = Subscription::where('status', SubscriptionStatus::Active)->sum('renewal_cost_usd') / 100;

        return round($annual / 12, 2);
    }

    /**
     * Revenue actually received in a given calendar month, from both
     * sources. Invoices are attributed by issued_date; direct renewals by
     * whichever date reflects when payment actually came in (confirmed,
     * then received, then falling back to the record's creation date).
     */
    private function revenueForMonth(int $year, int $month): float
    {
        $invoiceTotal = (float) $this->invoiceRevenueQuery()
            ->whereYear('issued_date', $year)
            ->whereMonth('issued_date', $month)
            ->sum('amount_paid');

        $renewalTotal = (float) $this->directRenewalRevenueQuery()
            ->whereYear($this->renewalAttributionDate(), $year)
            ->whereMonth($this->renewalAttributionDate(), $month)
            ->sum('client_cost_usd');

        return ($invoiceTotal + $renewalTotal) / 100;
    }

    /**
     * Invoices with money actually received — fully Paid or Partially Paid.
     * Summing amount_paid (rather than total_amount) means a partial
     * payment only contributes what was actually collected.
     *
     * @return Builder<Invoice>
     */
    private function invoiceRevenueQuery(): Builder
    {
        return Invoice::whereIn('status', [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid]);
    }

    /**
     * Renewals linked to an invoice are excluded — that revenue is already
     * counted once via the invoice itself.
     *
     * @return Builder<Renewal>
     */
    private function directRenewalRevenueQuery(): Builder
    {
        return Renewal::whereNull('invoice_id')
            ->whereIn('payment_status', [PaymentStatus::Renewed, PaymentStatus::Paid]);
    }

    private function renewalAttributionDate(): Expression
    {
        return DB::raw('COALESCE(renewal_confirmed_date, payment_received_date, created_at)');
    }
}

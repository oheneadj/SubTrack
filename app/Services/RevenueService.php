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
        $data = collect(range($months - 1, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            // Expenses: Estimated monthly provider costs (calculated from active subscriptions' renewal_cost_usd / 12)
            // Note: This is an "Expected Monthly Cost" baseline rather than literal expense tracking
            $expenses = (float) Subscription::where('status', SubscriptionStatus::Active)
                ->sum('renewal_cost_usd') / 12;

            return [
                'label' => $date->format('M Y'),
                'revenue' => $this->revenueForMonth($date->year, $date->month),
                'expenses' => round($expenses, 2),
            ];
        });

        return $data->toArray();
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

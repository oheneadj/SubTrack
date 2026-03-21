<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class RevenueService
{
    public function lastSixMonths(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            $date  = now()->subMonths($monthsAgo);
            $total = Invoice::where('status', '=', InvoiceStatus::Paid)
                ->whereYear('issued_date', '=', $date->year)
                ->whereMonth('issued_date', $date->month)
                ->sum('total_amount');

            return [
                'label'  => $date->format('M'),
                'total'  => (float) $total,
                'year'   => $date->year,
                'month'  => $date->month,
            ];
        });

        return $months->toArray();
    }

    public function currentMonthTotal(): float
    {
        return (float) Invoice::where('status', InvoiceStatus::Paid)
            ->whereYear('issued_date', now()->year)
            ->whereMonth('issued_date', now()->month)
            ->sum('total_amount');
    }

    public function previousMonthTotal(): float
    {
        return (float) Invoice::where('status', InvoiceStatus::Paid)
            ->whereYear('issued_date', now()->subMonth()->year)
            ->whereMonth('issued_date', now()->subMonth()->month)
            ->sum('total_amount');
    }

    public function monthOverMonthChange(): array
    {
        $current  = $this->currentMonthTotal();
        $previous = $this->previousMonthTotal();
        $diff     = $current - $previous;
        $pct      = $previous > 0 ? round(($diff / $previous) * 100) : 0;

        return [
            'current'    => $current,
            'previous'   => $previous,
            'diff'       => $diff,
            'percentage' => $pct,
            'direction'  => $diff >= 0 ? 'up' : 'down',
        ];
    }

    public function comparisonData(int $months = 12): array
    {
        $data = collect(range($months - 1, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            
            // Revenue: Paid Invoices for that month
            $revenue = (float) Invoice::where('status', InvoiceStatus::Paid)
                ->whereYear('issued_date', $date->year)
                ->whereMonth('issued_date', $date->month)
                ->sum('total_amount');

            // Expenses: Estimated monthly provider costs (calculated from active subscriptions' renewal_cost_usd / 12)
            // Note: This is an "Expected Monthly Cost" baseline rather than literal expense tracking
            $expenses = (float) \App\Models\Subscription::where('status', \App\Enums\SubscriptionStatus::Active)
                // We use established costs as a baseline for the line chart comparison
                ->sum('renewal_cost_usd') / 12;

            return [
                'label'    => $date->format('M Y'),
                'revenue'  => $revenue,
                'expenses' => round($expenses, 2),
            ];
        });

        return $data->toArray();
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Services\RevenueService;
use Illuminate\View\View;
use Livewire\Component;

class FinanceDashboard extends Component
{
    public function render(RevenueService $revenue): View
    {
        $comparisonData = $revenue->comparisonData(12);
        $totalRevenue = Invoice::where('status', InvoiceStatus::Paid)->sum('total_amount') / 100;

        $outstandingRevenue = Invoice::whereIn('status', [
            InvoiceStatus::Sent,
            InvoiceStatus::Overdue,
        ])->sum('total_amount') / 100;

        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::Active)->get();

        $annualRecurring = $activeSubscriptions->sum('client_renewal_cost_usd') / 100;
        $mrr = $annualRecurring / 12;

        $totalCosts = Renewal::sum('provider_cost_usd') / 100;
        $profit = Renewal::sum('client_cost_usd') / 100 - $totalCosts;

        // Recent Paid Invoices
        $recentInvoices = Invoice::with(['client'])
            ->where('status', InvoiceStatus::Paid)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // Upcoming Renewals
        $upcomingRenewals = Subscription::with(['provider', 'project.client'])
            ->where('status', SubscriptionStatus::Active)
            ->orderBy('expiry_date', 'asc')
            ->take(5)
            ->get();

        return view('livewire.dashboard.finance-dashboard', [
            'totalRevenue' => $totalRevenue,
            'outstandingRevenue' => $outstandingRevenue,
            'mrr' => $mrr,
            'totalCosts' => $totalCosts,
            'profit' => $profit,
            'recentInvoices' => $recentInvoices,
            'upcomingRenewals' => $upcomingRenewals,
            'comparisonData' => $comparisonData,
        ])->layout('components.layouts.app');
    }
}

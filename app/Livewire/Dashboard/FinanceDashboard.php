<?php

namespace App\Livewire\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Subscription;
use Livewire\Component;

class FinanceDashboard extends Component
{
    public function render()
    {
        $totalRevenue = Invoice::where('status', InvoiceStatus::Paid)->sum('total_amount');
        
        $outstandingRevenue = Invoice::whereIn('status', [
            InvoiceStatus::Sent, 
            InvoiceStatus::Overdue
        ])->sum('total_amount');

        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::Active)->get();
        
        $annualRecurring = $activeSubscriptions->sum('renewal_cost_usd');
        $mrr = $annualRecurring / 12;

        $totalCosts = $annualRecurring;

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
            'recentInvoices' => $recentInvoices,
            'upcomingRenewals' => $upcomingRenewals,
        ])->layout('components.layouts.app');
    }
}

<?php

namespace App\Livewire\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Services\NotificationService;
use App\Services\RevenueService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OverviewDashboard extends Component
{
    public array $revenueData    = [];
    public array $revenueChange  = [];
    public array $comparisonData = [];

    public function mount(RevenueService $revenue): void
    {
        $this->revenueData    = $revenue->lastSixMonths();
        $this->revenueChange  = $revenue->monthOverMonthChange();
        $this->comparisonData = $revenue->comparisonData(12);
    }

    #[Computed]
    public function criticalSubscriptions()
    {
        return Subscription::critical()
            ->with('project.client')
            ->orderBy('expiry_date')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function warningSubscriptions()
    {
        return Subscription::warning()
            ->with('project.client')
            ->orderBy('expiry_date')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentInvoices()
    {
        return Invoice::with('client')
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function activityFeed()
    {
        return DashboardActivityLog::with('client')
            ->latest()
            ->take(6)
            ->get();
    }

    #[Computed]
    public function financeStats(): array
    {
        $annualRecurring = Subscription::where('status', '=', \App\Enums\SubscriptionStatus::Active)->sum('renewal_cost_usd');

        return [
            'total_revenue' => Invoice::where('status', '=', \App\Enums\InvoiceStatus::Paid)->sum('total_amount'),
            'outstanding'   => Invoice::whereIn('status', [\App\Enums\InvoiceStatus::Sent, \App\Enums\InvoiceStatus::Overdue])->sum('total_amount'),
            'mrr'           => $annualRecurring / 12,
            'costs'         => $annualRecurring,
        ];
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'critical'       => Subscription::critical()->count(),
            'warning'        => Subscription::warning()->count(),
            'healthy'        => Subscription::healthy()->count(),
            'awaiting'       => Renewal::where('payment_status', '=', PaymentStatus::Invoiced)->count(),
            'overdue'        => Invoice::where('status', '=', InvoiceStatus::Overdue)->count(),
            'total_clients'  => Client::count(),
        ];
    }

    public function sendReminder(int $subscriptionId): void
    {
        $subscription = Subscription::with('project.client')->findOrFail($subscriptionId);
        $daysLeft     = $subscription->days_until_expiry;

        app(NotificationService::class)->sendExpiryReminder($subscription);

        session()->flash('success', "Reminder sent to {$subscription->project->client->name}.");
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview-dashboard');
    }
}

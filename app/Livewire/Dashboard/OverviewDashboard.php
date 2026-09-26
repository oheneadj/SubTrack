<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
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
    public array $revenueData = [];

    public array $revenueChange = [];

    public array $comparisonData = [];

    public function mount(RevenueService $revenue): void
    {
        $this->revenueData = $revenue->lastSixMonths();
        $this->revenueChange = $revenue->monthOverMonthChange();
        $this->comparisonData = $revenue->comparisonData(12);
    }

    #[Computed]
    public function criticalSubscriptions()
    {
        return Subscription::critical()
            ->with(['client', 'project.client'])
            ->orderBy('expiry_date')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function warningSubscriptions()
    {
        return Subscription::warning()
            ->with(['client', 'project.client'])
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
        $activeSubscriptions = Subscription::where('status', '=', SubscriptionStatus::Active)->get();
        $annualRecurringCents = $activeSubscriptions->sum('client_renewal_cost_usd');

        return [
            'total_revenue' => Invoice::where('status', '=', InvoiceStatus::Paid)->sum('total_amount') / 100,
            'outstanding' => Invoice::whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->sum('total_amount') / 100,
            'mrr' => $annualRecurringCents / 12 / 100,
            'costs' => Renewal::sum('provider_cost_usd') / 100,
        ];
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'critical' => Subscription::critical()->count(),
            'warning' => Subscription::warning()->count(),
            'healthy' => Subscription::healthy()->count(),
            'awaiting' => Renewal::where('payment_status', '=', PaymentStatus::Invoiced)->count(),
            'overdue' => Invoice::where('status', '=', InvoiceStatus::Overdue)->count(),
            'total_clients' => Client::count(),
        ];
    }

    public function sendReminder(string $subscriptionUlid): void
    {
        $subscription = Subscription::with(['client', 'project.client'])->where('ulid', $subscriptionUlid)->firstOrFail();

        app(NotificationService::class)->sendExpiryReminder($subscription);

        $clientName = $subscription->effective_client->name ?? 'client';
        session()->flash('success', "Reminder sent to {$clientName}.");
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview-dashboard');
    }
}

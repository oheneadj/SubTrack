<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\Notifies;
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
    use Notifies;

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
        $revenue = app(RevenueService::class);

        // All four figures come from RevenueService — the single source of
        // truth also used by the Finance dashboard — so the two pages can
        // never disagree on what these mean again (this file used to
        // duplicate the calculations independently, which is exactly how
        // 'costs' drifted out of sync: the Finance dashboard was fixed to
        // exclude renewals still Pending payment, but this copy wasn't).
        return [
            'total_revenue' => $revenue->totalRevenue(),
            'outstanding' => $revenue->outstandingRevenue(),
            'mrr' => $revenue->estimatedMonthlyRecurringRevenue(),
            'costs' => $revenue->totalProviderCosts(),
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
        $this->notifySuccess("Reminder sent to {$clientName}.");
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview-dashboard');
    }
}

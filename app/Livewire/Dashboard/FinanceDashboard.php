<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Services\RevenueService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class FinanceDashboard extends Component
{
    public function render(RevenueService $revenue): View
    {
        $comparisonData = $revenue->comparisonData(12);

        // Includes both paid invoices and renewals paid for directly without
        // ever being invoiced — see RevenueService for why both count.
        $totalRevenue = $revenue->totalRevenue();
        $outstandingRevenue = $revenue->outstandingRevenue();
        $draftInvoiceTotal = $revenue->draftInvoiceTotal();
        $mrr = $revenue->estimatedMonthlyRecurringRevenue();
        $totalCosts = $revenue->totalProviderCosts();
        $profit = $revenue->totalProfit();

        $recentPayments = $this->recentPayments();

        // Upcoming Renewals — a one-time purchase never renews, so it
        // doesn't belong on a list of upcoming renewal expenses even if its
        // (non-recurring) expiry date happens to be soon.
        $upcomingRenewals = Subscription::with(['provider', 'project.client'])
            ->where('status', SubscriptionStatus::Active)
            ->whereIn('renewal_type', [SubscriptionRenewalType::RecurringMonthly, SubscriptionRenewalType::RecurringAnnually])
            ->orderBy('expiry_date', 'asc')
            ->take(5)
            ->get();

        return view('livewire.dashboard.finance-dashboard', [
            'totalRevenue' => $totalRevenue,
            'outstandingRevenue' => $outstandingRevenue,
            'draftInvoiceTotal' => $draftInvoiceTotal,
            'mrr' => $mrr,
            'totalCosts' => $totalCosts,
            'profit' => $profit,
            'recentPayments' => $recentPayments,
            'upcomingRenewals' => $upcomingRenewals,
            'comparisonData' => $comparisonData,
        ])->layout('components.layouts.app');
    }

    /**
     * The 5 most recent payments received, merged from both revenue
     * sources (paid invoices and directly-paid renewals) so neither is
     * silently missing from the activity feed.
     *
     * @return Collection<int, object>
     */
    private function recentPayments(): Collection
    {
        $invoicePayments = Invoice::with('client')
            ->whereIn('status', [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn (Invoice $invoice) => (object) [
                'type' => 'invoice',
                'client_name' => $invoice->client?->name ?? 'Unknown Client',
                // amount_paid, not total_amount — a Partially Paid invoice
                // hasn't had its full total received yet, only this much.
                'amount' => $invoice->amount_paid / 100,
                'date' => $invoice->updated_at,
                'reference' => $invoice->invoice_number,
                'route' => route('invoices.edit', $invoice),
            ]);

        $renewalPayments = Renewal::with('subscription.project.client', 'subscription.client')
            ->whereNull('invoice_id')
            ->whereIn('payment_status', [PaymentStatus::Renewed, PaymentStatus::Paid])
            ->latest('renewal_confirmed_date')
            ->take(5)
            ->get()
            ->map(function (Renewal $renewal) {
                $subscription = $renewal->subscription;
                $client = $subscription?->effective_client;

                return (object) [
                    'type' => 'renewal',
                    'client_name' => $client?->name ?? 'Unknown Client',
                    'amount' => $renewal->client_cost_usd / 100,
                    'date' => $renewal->renewal_confirmed_date ?? $renewal->payment_received_date ?? $renewal->created_at,
                    'reference' => $subscription?->domain_name ?: $subscription?->service_type?->label(),
                    'route' => $subscription ? route('subscriptions.show', $subscription) : null,
                ];
            });

        return $invoicePayments->concat($renewalPayments)
            ->sortByDesc('date')
            ->take(5)
            ->values();
    }
}

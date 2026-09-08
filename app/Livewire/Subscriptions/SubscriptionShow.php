<?php

declare(strict_types=1);

namespace App\Livewire\Subscriptions;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Renewal;
use App\Models\Subscription;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shows full details for a single subscription, including renewal history.
 */
class SubscriptionShow extends Component
{
    public Subscription $subscription;

    public bool $showRenewalModal = false;

    public string $renewalMode = 'years';

    public int $renewalYears = 1;

    public int $renewalMonths = 1;

    public string $customExpiryDate = '';

    public float $renewalProviderCost = 0;

    public string $notes = '';

    public function mount(Subscription $subscription): void
    {
        $this->subscription = $subscription->load(['client', 'project.client', 'provider', 'renewals.invoice']);
        $this->notes = $subscription->notes ?? '';
    }

    /** Save the notes field in place, without leaving the show page. */
    public function saveNotes(): void
    {
        $this->validate(['notes' => 'nullable|string']);

        $this->subscription->update(['notes' => $this->notes]);

        session()->flash('success', 'Notes updated.');
    }

    #[Computed]
    public function client()
    {
        return $this->subscription->effective_client;
    }

    #[Computed]
    public function renewals()
    {
        return $this->subscription->renewals()->with('invoice')->latest('due_date')->get();
    }

    #[Computed]
    public function stats(): array
    {
        $renewals = $this->subscription->renewals;

        return [
            'total_renewals' => $renewals->count(),
            'total_paid' => $renewals->where('payment_status.value', 'Paid')->sum('client_cost_usd') / 100,
            'total_cost' => $renewals->sum('provider_cost_usd') / 100,
            'total_margin' => $renewals->sum(fn ($r) => $r->margin) / 100,
        ];
    }

    /**
     * Open the renewal modal, defaulting the increment mode to match this
     * subscription's billing cycle (monthly subscriptions default to adding
     * months, annual ones to adding years) — the custom-date override is
     * always available regardless.
     */
    public function openRenewalModal(): void
    {
        $this->renewalMode = $this->subscription->renewal_type->cycleMonths() === 1 ? 'months' : 'years';
        $this->renewalYears = 1;
        $this->renewalMonths = 1;
        $this->customExpiryDate = $this->subscription->expiry_date
            ->copy()->addYear()->format('Y-m-d');
        $this->renewalProviderCost = round($this->subscription->renewal_cost_usd / 100, 2);
        $this->showRenewalModal = true;
    }

    public function processRenewal(): void
    {
        $this->validate([
            'renewalMode' => 'required|in:years,months,date',
            'renewalYears' => 'required_if:renewalMode,years|integer|min:1|max:10',
            'renewalMonths' => 'required_if:renewalMode,months|integer|min:1|max:36',
            'customExpiryDate' => 'required_if:renewalMode,date|date',
            'renewalProviderCost' => 'required|numeric|min:0',
        ]);

        $oldExpiry = $this->subscription->expiry_date;

        $providerCostCents = (int) round($this->renewalProviderCost * 100);
        $markup = (float) ($this->subscription->markup_percentage ?? 0);
        $markedUpCost = (int) round($providerCostCents * (1 + $markup / 100));

        if ($this->renewalMode === 'years') {
            $newExpiry = $oldExpiry->copy()->addYears($this->renewalYears);
            $clientCost = $markedUpCost * $this->renewalYears;
            $note = "Automated renewal. Expiry rolled from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} (+{$this->renewalYears} years).";
        } elseif ($this->renewalMode === 'months') {
            $newExpiry = $oldExpiry->copy()->addMonths($this->renewalMonths);
            $clientCost = $markedUpCost;
            $note = "Automated renewal. Expiry rolled from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} (+{$this->renewalMonths} months).";
        } else {
            $newExpiry = Carbon::parse($this->customExpiryDate);
            $clientCost = $markedUpCost;
            $note = "Manual date renewal. Expiry set from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')}.";
        }

        Renewal::create([
            'subscription_id' => $this->subscription->id,
            'due_date' => $oldExpiry,
            'provider_cost_usd' => $providerCostCents,
            'client_cost_usd' => $clientCost,
            'payment_status' => PaymentStatus::Renewed,
            'renewal_confirmed_date' => now(),
            'notes' => $note,
        ]);

        $updateData = [
            'expiry_date' => $newExpiry,
            'status' => SubscriptionStatus::Active,
        ];

        // If the provider changed their price, keep the subscription in sync
        if ($providerCostCents !== $this->subscription->renewal_cost_usd) {
            $updateData['renewal_cost_usd'] = $providerCostCents;
        }

        $this->subscription->update($updateData);

        // Refresh so the new expiry and renewal row appear immediately
        $this->subscription->refresh()->load(['client', 'project.client', 'provider', 'renewals.invoice']);

        $this->showRenewalModal = false;

        session()->flash('success', "Renewal confirmed. Next expiry: {$newExpiry->format('M d, Y')}");
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.subscriptions.subscription-show');
    }
}

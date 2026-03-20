<?php

namespace App\Livewire\Renewals;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Renewal;
use App\Models\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class RenewalTracker extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public bool $showRenewalModal = false;
    public ?int $renewingSubscriptionId = null;
    public string $renewalMode = 'years';
    public int $renewalYears = 1;
    public string $customExpiryDate = '';

    #[Computed]
    public function subscriptions()
    {
        return Subscription::with(['project.client', 'provider'])
            ->where('status', '!=', SubscriptionStatus::Cancelled)
            ->when($this->search, function ($query) {
                $query->where('domain_name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('provider', fn($p) => $p->where('name', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('project', function ($q) {
                        $q->where('project_name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('expiry_date', 'asc')
            ->paginate(15);
    }

    public function openRenewalModal(int $subscriptionId): void
    {
        $this->renewingSubscriptionId = $subscriptionId;
        $this->renewalMode = 'years';
        $this->renewalYears = 1;
        
        $sub = Subscription::find($subscriptionId);
        if ($sub && $sub->expiry_date) {
            $this->customExpiryDate = $sub->expiry_date->copy()->addYear()->format('Y-m-d');
        } else {
            $this->customExpiryDate = now()->addYear()->format('Y-m-d');
        }

        $this->showRenewalModal = true;
    }

    public function processRenewal(): void
    {
        $this->validate([
            'renewalMode' => 'required|in:years,date',
            'renewalYears' => 'required_if:renewalMode,years|integer|min:1|max:10',
            'customExpiryDate' => 'required_if:renewalMode,date|date',
        ]);

        if (!$this->renewingSubscriptionId) return;

        $subscription = Subscription::findOrFail($this->renewingSubscriptionId);
        
        $oldExpiry = $subscription->expiry_date;
        
        if ($this->renewalMode === 'years') {
            $newExpiry = $oldExpiry->copy()->addYears($this->renewalYears);
            $clientCost = ($subscription->renewal_cost_usd ?? 0) * $this->renewalYears;
            $note = "Automated renewal. Expiry rolled from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} (+{$this->renewalYears} years).";
        } else {
            $newExpiry = \Carbon\Carbon::parse($this->customExpiryDate);
            $clientCost = $subscription->renewal_cost_usd ?? 0;
            $note = "Manual date renewal. Expiry set from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')}.";
        }
        
        Renewal::create([
            'subscription_id' => $subscription->id,
            'due_date' => $oldExpiry,
            'provider_cost_usd' => $subscription->purchase_cost_usd ?? 0,
            'client_cost_usd' => $clientCost,
            'payment_status' => PaymentStatus::Renewed,
            'renewal_confirmed_date' => now(),
            'notes' => $note,
        ]);

        $subscription->update([
            'expiry_date' => $newExpiry,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->showRenewalModal = false;
        $this->renewingSubscriptionId = null;

        session()->flash('success', "Renewal confirmed for {$subscription->domain_name}. Next expiry: {$newExpiry->format('M d, Y')}");
    }

    public function render()
    {
        return view('livewire.renewals.renewal-tracker');
    }
}

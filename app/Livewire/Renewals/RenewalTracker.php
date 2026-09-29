<?php

declare(strict_types=1);

namespace App\Livewire\Renewals;

use App\Actions\PrepareRenewalAction;
use App\Enums\SubscriptionStatus;
use App\Livewire\Concerns\Notifies;
use App\Models\Subscription;
use App\Traits\WithSorting;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class RenewalTracker extends Component
{
    use Notifies, WithPagination, WithSorting;

    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public string $statusFilter = '';

    public bool $showRenewalModal = false;

    public ?int $renewingSubscriptionId = null;

    public string $renewalMode = 'years';

    public int $renewalYears = 1;

    public string $customExpiryDate = '';

    /** The subscription currently open in the renewal modal, or null if none is selected. */
    #[Computed]
    public function subscriptionToRenew(): ?Subscription
    {
        if (! $this->renewingSubscriptionId) {
            return null;
        }

        return collect($this->subscriptions->items())->firstWhere('id', $this->renewingSubscriptionId);
    }

    #[Computed]
    public function subscriptions()
    {
        $query = Subscription::with(['client', 'project.client', 'provider'])
            ->where('status', '!=', SubscriptionStatus::Cancelled)
            // Only recurring subscriptions need renewal processing — one-time
            // purchases simply lapse at their expiry date.
            ->whereIn('renewal_type', ['RecurringMonthly', 'RecurringAnnually'])
            ->when($this->search, function ($query) {
                $query->where('domain_name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('provider', fn ($p) => $p->where('name', 'like', '%'.$this->search.'%'))
                    ->orWhereHas('project', function ($q) {
                        $q->where('project_name', 'like', '%'.$this->search.'%');
                    });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            });

        return $this->applySorting($query)->paginate(15);
    }

    public function openRenewalModal(string $subscriptionUlid): void
    {
        $sub = Subscription::where('ulid', $subscriptionUlid)->firstOrFail();
        $this->renewingSubscriptionId = $sub->id;
        $this->renewalMode = 'years';
        $this->renewalYears = 1;
        $this->customExpiryDate = $sub->expiry_date
            ? $sub->expiry_date->copy()->addYear()->format('Y-m-d')
            : now()->addYear()->format('Y-m-d');
        $this->showRenewalModal = true;
    }

    /**
     * Starts the renewal (records what's owed and raises an invoice for
     * it) via the same PrepareRenewalAction used on the subscription's own
     * page — this only prepares it; taking payment and processing it (to
     * actually roll the expiry) happens from there, not here.
     */
    public function processRenewal(PrepareRenewalAction $action): void
    {
        $this->validate([
            'renewalMode' => 'required|in:years,date',
            'renewalYears' => 'required_if:renewalMode,years|integer|min:1|max:10',
            'customExpiryDate' => 'required_if:renewalMode,date|date',
        ]);

        if (! $this->renewingSubscriptionId) {
            return;
        }

        $subscription = Subscription::findOrFail($this->renewingSubscriptionId);
        $oldExpiry = $subscription->expiry_date;

        if ($this->renewalMode === 'years') {
            $newExpiry = $oldExpiry->copy()->addYears($this->renewalYears);
            $note = "Renewal prepared from the Renewal Tracker. Expiry will roll from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} (+{$this->renewalYears} years) once paid.";
        } else {
            $newExpiry = Carbon::parse($this->customExpiryDate);
            $note = "Renewal prepared from the Renewal Tracker. Expiry will be set from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} once paid.";
        }

        try {
            $action->execute($subscription, $subscription->renewal_cost_usd ?? 0, $newExpiry, $note);
        } catch (RuntimeException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        $this->showRenewalModal = false;
        $this->renewingSubscriptionId = null;

        $this->notifySuccess("Renewal prepared for {$subscription->domain_name} — an invoice has been raised. Take payment and process it from the subscription's page.");
    }

    public function render()
    {
        return view('livewire.renewals.renewal-tracker');
    }
}

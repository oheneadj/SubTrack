<?php

declare(strict_types=1);

namespace App\Livewire\Subscriptions;

use App\Actions\PrepareRenewalAction;
use App\Actions\ProcessRenewalAction;
use App\Exceptions\RenewalAlreadyProcessedException;
use App\Exceptions\RenewalNotPaidException;
use App\Livewire\Concerns\RecordsManualPayments;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\NotificationService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

/**
 * Shows full details for a single subscription, including renewal history.
 *
 * The renewal lifecycle is: PrepareRenewalAction (records what's owed and
 * raises an invoice for it, without touching the expiry date) -> payment is
 * taken against that invoice, manually (RecordsManualPayments trait) or via
 * a payment link (an emailed invoice with a Stripe checkout URL) -> once
 * paid, a receipt is generated (manually for a manual payment, automatically
 * for a gateway one — see AbstractGateway::markInvoicePaid()) -> only then
 * can ProcessRenewalAction roll the subscription's expiry date.
 */
class SubscriptionShow extends Component
{
    use RecordsManualPayments;

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

        $this->notifySuccess('Notes updated.');
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

    /**
     * Starts the renewal: records what's owed and raises an invoice for it.
     * Does not touch the subscription's expiry date — that only happens
     * once it's paid and processRenewal() is run on it, further down.
     */
    public function prepareRenewal(PrepareRenewalAction $action): void
    {
        $this->validate([
            'renewalMode' => 'required|in:years,months,date',
            'renewalYears' => 'required_if:renewalMode,years|integer|min:1|max:10',
            'renewalMonths' => 'required_if:renewalMode,months|integer|min:1|max:36',
            'customExpiryDate' => 'required_if:renewalMode,date|date',
            'renewalProviderCost' => 'required|numeric|min:0',
        ]);

        $oldExpiry = $this->subscription->expiry_date;

        if ($this->renewalMode === 'years') {
            $newExpiry = $oldExpiry->copy()->addYears($this->renewalYears);
            $note = "Renewal prepared. Expiry will roll from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} (+{$this->renewalYears} years) once paid.";
        } elseif ($this->renewalMode === 'months') {
            $newExpiry = $oldExpiry->copy()->addMonths($this->renewalMonths);
            $note = "Renewal prepared. Expiry will roll from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} (+{$this->renewalMonths} months) once paid.";
        } else {
            $newExpiry = Carbon::parse($this->customExpiryDate);
            $note = "Renewal prepared. Expiry will be set from {$oldExpiry->format('Y-m-d')} to {$newExpiry->format('Y-m-d')} once paid.";
        }

        try {
            $action->execute(
                $this->subscription,
                (int) round($this->renewalProviderCost * 100),
                $newExpiry,
                $note,
            );
        } catch (RuntimeException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        $this->subscription->refresh()->load(['client', 'project.client', 'provider', 'renewals.invoice']);
        unset($this->renewals, $this->stats);

        $this->showRenewalModal = false;

        $this->notifySuccess('Renewal prepared — an invoice has been raised. Take payment, then process the renewal once it\'s paid.');
    }

    /** Emails the renewal's invoice to the client, with a link to pay it online. */
    public function sendPaymentLink(string $renewalUlid, NotificationService $notificationService): void
    {
        $renewal = $this->subscription->renewals()->where('ulid', $renewalUlid)->firstOrFail();

        if (! $renewal->invoice) {
            return;
        }

        $notificationService->sendInvoice($renewal->invoice);

        $this->notifySuccess('Payment link emailed to the client.');
    }

    /**
     * Applies an already-paid renewal to the subscription — rolls the
     * expiry date and marks it processed. Only reachable once payment has
     * been confirmed (Renewal::isAwaitingProcessing()).
     */
    public function processRenewal(string $renewalUlid, ProcessRenewalAction $action): void
    {
        $renewal = $this->subscription->renewals()->where('ulid', $renewalUlid)->firstOrFail();

        try {
            $renewal = $action->execute($renewal);
        } catch (RenewalNotPaidException|RenewalAlreadyProcessedException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        $this->subscription->refresh()->load(['client', 'project.client', 'provider', 'renewals.invoice']);
        unset($this->renewals, $this->stats);

        $this->notifySuccess("Renewal processed. Next expiry: {$renewal->subscription->expiry_date->format('M d, Y')}");
    }

    /** Refreshes computed state after a manual payment is recorded against a renewal's invoice. */
    protected function afterPaymentRecorded(Invoice $invoice): void
    {
        unset($this->renewals, $this->stats);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.subscriptions.subscription-show');
    }
}

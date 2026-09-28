<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Renewal;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\InvoiceNumberService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Starts a renewal: records what's owed (a Renewal row, payment_status
 * Pending) and raises an invoice for it — but never touches the
 * subscription's expiry date. That only happens once the renewal is paid
 * and ProcessRenewalAction runs; take payment, then generate a receipt,
 * then process the renewal is the intended order end to end.
 */
class PrepareRenewalAction
{
    public function __construct(
        private readonly InvoiceNumberService $invoiceNumbers,
    ) {}

    /**
     * @throws RuntimeException if the subscription has no linked client
     */
    public function execute(Subscription $subscription, int $providerCostCents, CarbonInterface $newExpiry, ?string $notes = null): Renewal
    {
        $client = $subscription->effective_client;
        if (! $client) {
            throw new RuntimeException('Cannot prepare a renewal for a subscription with no linked client.');
        }

        $markup = (float) ($subscription->markup_percentage ?? 0);
        $clientCostCents = (int) round($providerCostCents * (1 + $markup / 100));

        return DB::transaction(function () use ($subscription, $client, $providerCostCents, $clientCostCents, $newExpiry, $notes) {
            $renewal = Renewal::create([
                'subscription_id' => $subscription->id,
                'due_date' => $subscription->expiry_date,
                'new_expiry_date' => $newExpiry->toDateString(),
                'provider_cost_usd' => $providerCostCents,
                'client_cost_usd' => $clientCostCents,
                'payment_status' => PaymentStatus::Pending,
                'notes' => $notes,
            ]);

            $invoice = $this->resolveInvoice($subscription, $client->id, $renewal, $clientCostCents);
            $renewal->update(['invoice_id' => $invoice->id]);

            return $renewal->fresh('invoice');
        });
    }

    /**
     * Reuses a still-open (Draft/Sent) invoice already raised for this
     * subscription's current cycle — e.g. by an automated expiry reminder
     * (see NotificationService::resolveInvoiceForReminder()) — instead of
     * billing the client twice for the same renewal.
     */
    private function resolveInvoice(Subscription $subscription, int $clientId, Renewal $renewal, int $clientCostCents): Invoice
    {
        $existingItem = InvoiceItem::where('subscription_id', $subscription->id)
            ->whereHas('invoice', fn ($query) => $query->whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Sent]))
            ->latest('id')
            ->first();

        if ($existingItem) {
            $existingItem->update(['renewal_id' => $renewal->id]);

            return $existingItem->invoice;
        }

        $serviceName = $subscription->domain_name ?: $subscription->service_type->label();
        $now = CarbonImmutable::now();
        $dueDays = (int) Setting::get('invoice_due_days', 14);

        $invoice = Invoice::create([
            'client_id' => $clientId,
            'project_id' => $subscription->project_id,
            'invoice_number' => $this->invoiceNumbers->generate(),
            'issued_date' => $now->toDateString(),
            'due_date' => $now->addDays($dueDays)->toDateString(),
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal' => $clientCostCents,
            'total_amount' => $clientCostCents,
            'status' => InvoiceStatus::Draft,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'renewal_id' => $renewal->id,
            'subscription_id' => $subscription->id,
            'description' => "Renewal: {$serviceName}",
            'period' => "Expiring {$subscription->expiry_date->format('M d, Y')}",
            'quantity' => 1,
            'unit_price' => $clientCostCents,
            'total' => $clientCostCents,
        ]);

        return $invoice;
    }
}

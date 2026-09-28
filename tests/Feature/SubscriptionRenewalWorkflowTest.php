<?php

declare(strict_types=1);

use App\Actions\PrepareRenewalAction;
use App\Actions\ProcessRenewalAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\RenewalAlreadyProcessedException;
use App\Exceptions\RenewalNotPaidException;
use App\Livewire\Subscriptions\SubscriptionShow;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Receipt;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\AbstractGateway;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Covers the intended renewal lifecycle end to end: PrepareRenewalAction
 * (raises an invoice, never touches expiry) -> payment is taken against
 * that invoice -> the linked Renewal auto-settles to Paid -> only then can
 * ProcessRenewalAction roll the subscription's expiry date.
 */
function makeRenewalTestSubscription(int $renewalCostUsd = 1000, float $markup = 0): Subscription
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    return Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme-'.uniqid().'.com',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(5),
        'purchase_cost_usd' => $renewalCostUsd,
        'renewal_cost_usd' => $renewalCostUsd,
        'markup_percentage' => $markup,
        'status' => 'Active',
    ]);
}

test('preparing a renewal raises an invoice and a Pending renewal, without touching the expiry date', function () {
    $subscription = makeRenewalTestSubscription(1000, 10);
    $originalExpiry = $subscription->expiry_date;

    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    expect($renewal->payment_status)->toBe(PaymentStatus::Pending)
        ->and($renewal->invoice)->not->toBeNull()
        ->and($renewal->invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($renewal->invoice->total_amount)->toBe(1100) // 10% markup on $10.00
        ->and($renewal->new_expiry_date->toDateString())->toBe(now()->addYear()->toDateString());

    expect($subscription->fresh()->expiry_date->toDateString())->toBe($originalExpiry->toDateString());
});

test('processing a renewal before it is paid is rejected', function () {
    $subscription = makeRenewalTestSubscription();
    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    expect(fn () => app(ProcessRenewalAction::class)->execute($renewal))
        ->toThrow(RenewalNotPaidException::class);

    expect($subscription->fresh()->expiry_date->toDateString())->not->toBe(now()->addYear()->toDateString());
});

test('recording a manual payment against the renewal invoice settles the renewal automatically', function () {
    $subscription = makeRenewalTestSubscription(1000);
    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    (new RecordManualPaymentAction)->execute($renewal->invoice, 1000);

    expect($renewal->fresh()->payment_status)->toBe(PaymentStatus::Paid)
        ->and($renewal->fresh()->payment_received_date)->not->toBeNull()
        ->and($renewal->fresh()->renewal_confirmed_date)->toBeNull(); // paid, but not yet processed
});

test('a partial payment does not settle the renewal', function () {
    $subscription = makeRenewalTestSubscription(1000);
    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    (new RecordManualPaymentAction)->execute($renewal->invoice, 400);

    expect($renewal->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('processing a paid renewal rolls the expiry date and marks it processed', function () {
    $subscription = makeRenewalTestSubscription(1000);
    $newExpiry = now()->addYear();
    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, $newExpiry);
    (new RecordManualPaymentAction)->execute($renewal->invoice, 1000);

    app(ProcessRenewalAction::class)->execute($renewal->fresh());

    expect($subscription->fresh()->expiry_date->toDateString())->toBe($newExpiry->toDateString())
        ->and($renewal->fresh()->renewal_confirmed_date)->not->toBeNull();
});

test('processing an already-processed renewal is rejected', function () {
    $subscription = makeRenewalTestSubscription(1000);
    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());
    (new RecordManualPaymentAction)->execute($renewal->invoice, 1000);
    app(ProcessRenewalAction::class)->execute($renewal->fresh());

    expect(fn () => app(ProcessRenewalAction::class)->execute($renewal->fresh()))
        ->toThrow(RenewalAlreadyProcessedException::class);
});

test('a receipt is auto-generated when a renewal invoice is paid via a gateway webhook', function () {
    $subscription = makeRenewalTestSubscription(1000);
    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());
    $invoice = $renewal->invoice;

    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'gateway' => 'stripe',
        'method' => 'card',
        'amount' => $invoice->total_amount,
        'currency' => 'usd',
        'status' => 'pending',
    ]);

    $gateway = new class extends AbstractGateway
    {
        public function exposedMarkInvoicePaid($invoice, $payment): void
        {
            $this->markInvoicePaid($invoice, $payment);
        }
    };
    $gateway->exposedMarkInvoicePaid($invoice, $payment);

    expect(Receipt::where('invoice_id', $invoice->id)->count())->toBe(1);
    expect($renewal->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

test('the subscription show page walks through prepare, pay, and process', function () {
    $subscription = makeRenewalTestSubscription(1000);

    $component = Livewire::actingAs(User::factory()->create())
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->set('renewalProviderCost', 10)
        ->set('renewalMode', 'years')
        ->set('renewalYears', 1)
        ->call('prepareRenewal')
        ->assertHasNoErrors();

    $renewal = $subscription->renewals()->latest()->first();
    expect($renewal)->not->toBeNull()
        ->and($renewal->invoice)->not->toBeNull();

    $component->call('openRecordPayment', $renewal->invoice->ulid)
        ->set('recordPaymentAmount', 10)
        ->call('submitRecordPayment')
        ->assertHasNoErrors();

    expect($renewal->fresh()->payment_status)->toBe(PaymentStatus::Paid);

    $component->call('processRenewal', $renewal->ulid)
        ->assertHasNoErrors();

    expect($subscription->fresh()->status->value)->toBe('Active');
    expect($renewal->fresh()->renewal_confirmed_date)->not->toBeNull();
});

test('the subscription show page can email a payment link for a pending renewal', function () {
    Mail::fake();

    $subscription = makeRenewalTestSubscription(1000);
    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    Livewire::actingAs(User::factory()->create())
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('sendPaymentLink', $renewal->ulid)
        ->assertHasNoErrors();

    expect($renewal->invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
    Mail::assertQueued(InvoiceMail::class);
});

<?php

declare(strict_types=1);

use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentRecordStatus;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Livewire\Invoices\InvoiceIndex;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Services\RevenueService;
use Livewire\Livewire;

function makePartialPaymentInvoice(int $totalAmountCents = 10000): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-PARTIAL-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => $totalAmountCents,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('recording a partial manual payment moves the invoice to Partially Paid and tracks the balance', function () {
    $invoice = makePartialPaymentInvoice(10000);

    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    expect($payment->status)->toBe(PaymentRecordStatus::Succeeded)
        ->and($payment->gateway)->toBe('manual');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->amount_paid)->toBe(4000)
        ->and($invoice->balance_due)->toBe(6000)
        ->and($invoice->isPaid())->toBeFalse();
});

test('recording a payment covering the full remaining balance marks the invoice Paid', function () {
    $invoice = makePartialPaymentInvoice(10000);

    (new RecordManualPaymentAction)->execute($invoice, 4000);
    (new RecordManualPaymentAction)->execute($invoice->fresh(), 6000);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->amount_paid)->toBe(10000)
        ->and($invoice->balance_due)->toBe(0)
        ->and($invoice->isPaid())->toBeTrue();
});

test('recording a payment larger than the balance due is rejected', function () {
    $invoice = makePartialPaymentInvoice(10000);

    expect(fn () => (new RecordManualPaymentAction)->execute($invoice, 10001))
        ->toThrow(InvalidPaymentAmountException::class);

    expect(fn () => (new RecordManualPaymentAction)->execute($invoice, 0))
        ->toThrow(InvalidPaymentAmountException::class);
});

test('recording a payment against an already-paid invoice is rejected', function () {
    $invoice = makePartialPaymentInvoice(10000);
    $invoice->update(['status' => InvoiceStatus::Paid]);

    expect(fn () => (new RecordManualPaymentAction)->execute($invoice, 100))
        ->toThrow(InvoiceAlreadyPaidException::class);
});

test('the invoice index Record Payment modal records a partial payment', function () {
    $invoice = makePartialPaymentInvoice(10000);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->call('openRecordPayment', $invoice->ulid)
        ->assertSet('recordPaymentAmount', 100.0)
        ->set('recordPaymentAmount', 25)
        ->call('submitRecordPayment')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->amount_paid)->toBe(2500);
});

test('the invoice index Record Payment modal rejects an amount over the balance due', function () {
    $invoice = makePartialPaymentInvoice(10000);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->call('openRecordPayment', $invoice->ulid)
        ->set('recordPaymentAmount', 500)
        ->call('submitRecordPayment')
        ->assertHasErrors('recordPaymentAmount');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test('RevenueService only counts the amount actually received for a partially paid invoice', function () {
    $invoice = makePartialPaymentInvoice(10000);
    (new RecordManualPaymentAction)->execute($invoice, 3000);

    expect(app(RevenueService::class)->totalRevenue())->toBe(30.0);
});

test('marking status Paid directly self-corrects amount_paid to the full total', function () {
    $invoice = makePartialPaymentInvoice(10000);

    $invoice->update(['status' => InvoiceStatus::Paid]);

    expect($invoice->fresh()->amount_paid)->toBe(10000);
});

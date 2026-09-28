<?php

declare(strict_types=1);

use App\Actions\EditManualPaymentAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Exceptions\InvalidPaymentDateException;
use App\Livewire\Invoices\InvoiceIndex;
use App\Livewire\Receipts\ReceiptIndex;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

function makePaymentDateTestInvoice(int $totalAmountCents = 10000): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-DATE-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => $totalAmountCents,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('a manual payment defaults to today when no date is given', function () {
    $invoice = makePaymentDateTestInvoice();

    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);

    expect($payment->paid_at->toDateString())->toBe(CarbonImmutable::now()->toDateString());
});

test('a manual payment can be backdated to a past date', function () {
    $invoice = makePaymentDateTestInvoice();
    $pastDate = CarbonImmutable::now()->subDays(5);

    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000, $pastDate);

    expect($payment->paid_at->toDateString())->toBe($pastDate->toDateString());
});

test('a manual payment dated in the future is rejected', function () {
    $invoice = makePaymentDateTestInvoice();
    $futureDate = CarbonImmutable::now()->addDays(3);

    expect(fn () => (new RecordManualPaymentAction)->execute($invoice, 10000, $futureDate))
        ->toThrow(InvalidPaymentDateException::class);
});

test('the record payment modal defaults to today and rejects a future date', function () {
    $invoice = makePaymentDateTestInvoice();

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->call('openRecordPayment', $invoice->ulid)
        ->assertSet('recordPaymentDate', now()->format('Y-m-d'))
        ->set('recordPaymentDate', now()->addDay()->format('Y-m-d'))
        ->call('submitRecordPayment')
        ->assertHasErrors('recordPaymentDate');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test('the record payment modal accepts a backdated payment date', function () {
    $invoice = makePaymentDateTestInvoice();
    $pastDate = now()->subDays(2)->format('Y-m-d');

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->call('openRecordPayment', $invoice->ulid)
        ->set('recordPaymentDate', $pastDate)
        ->call('submitRecordPayment')
        ->assertHasNoErrors();

    $payment = $invoice->fresh()->payments()->first();
    expect($payment->paid_at->toDateString())->toBe($pastDate);
});

test('editing a payment can correct its received date, but not to a future one', function () {
    $invoice = makePaymentDateTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);
    $correctedDate = CarbonImmutable::now()->subDays(3);

    (new EditManualPaymentAction)->execute($payment, 4000, $correctedDate);

    expect($payment->fresh()->paid_at->toDateString())->toBe($correctedDate->toDateString());

    expect(fn () => (new EditManualPaymentAction)->execute($payment->fresh(), 4000, CarbonImmutable::now()->addDay()))
        ->toThrow(InvalidPaymentDateException::class);
});

test('the edit payment modal pre-fills the current paid date and lets it be corrected', function () {
    $invoice = makePaymentDateTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);
    $correctedDate = now()->subDays(4)->format('Y-m-d');

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openEditPayment', $payment->ulid)
        ->assertSet('editPaymentDate', now()->format('Y-m-d'))
        ->set('editPaymentDate', $correctedDate)
        ->call('submitEditPayment')
        ->assertHasNoErrors();

    expect($payment->fresh()->paid_at->toDateString())->toBe($correctedDate);
});

test('the edit payment modal rejects a future paid date', function () {
    $invoice = makePaymentDateTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openEditPayment', $payment->ulid)
        ->set('editPaymentDate', now()->addDay()->format('Y-m-d'))
        ->call('submitEditPayment')
        ->assertHasErrors('editPaymentDate');
});

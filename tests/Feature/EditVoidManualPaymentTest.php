<?php

declare(strict_types=1);

use App\Actions\EditManualPaymentAction;
use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Actions\VoidManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentRecordStatus;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\PaymentNotEditableException;
use App\Exceptions\PaymentNotVoidableException;
use App\Livewire\Receipts\ReceiptIndex;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

function makePaymentTestInvoice(int $totalAmountCents = 10000): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-PAY-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => $totalAmountCents,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('a freshly recorded manual payment is editable', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    expect($payment->isEditable())->toBeTrue()
        ->and($payment->isVoidable())->toBeTrue();
});

test('editing a payment updates the amount and recalculates the invoice', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    (new EditManualPaymentAction)->execute($payment, 6000);

    expect($payment->fresh()->amount)->toBe(6000)
        ->and($invoice->fresh()->amount_paid)->toBe(6000)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::PartiallyPaid);
});

test('editing a payment records a PaymentEdited activity log entry', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    (new EditManualPaymentAction)->execute($payment, 6000);

    expect(DashboardActivityLog::where('event_type', 'payment.edited')->count())->toBe(1);
});

test('a payment is no longer editable once a receipt has been generated for the invoice', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);
    app(GenerateInvoiceReceiptAction::class)->execute($invoice->fresh());

    expect($payment->fresh()->isEditable())->toBeFalse();

    expect(fn () => (new EditManualPaymentAction)->execute($payment->fresh(), 5000))
        ->toThrow(PaymentNotEditableException::class);
});

test('editing a payment beyond the invoice total is rejected', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    expect(fn () => (new EditManualPaymentAction)->execute($payment, 20000))
        ->toThrow(InvalidPaymentAmountException::class);
});

test('a gateway payment is never editable or voidable', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'gateway' => 'stripe',
        'method' => 'card',
        'amount' => 10000,
        'currency' => 'usd',
        'status' => PaymentRecordStatus::Succeeded,
    ]);

    expect($payment->isEditable())->toBeFalse()
        ->and($payment->isVoidable())->toBeFalse();

    expect(fn () => (new VoidManualPaymentAction)->execute($payment))
        ->toThrow(PaymentNotVoidableException::class);
});

test('voiding a payment excludes it from amount_paid but keeps the original record', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    (new VoidManualPaymentAction)->execute($payment, 'Entered wrong amount');

    $payment->refresh();
    expect($payment->status)->toBe(PaymentRecordStatus::Voided)
        ->and($payment->amount)->toBe(4000)
        ->and($payment->void_reason)->toBe('Entered wrong amount')
        ->and($invoice->fresh()->amount_paid)->toBe(0)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test('voiding the only payment on an invoice reverts its status off Partially Paid', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    (new VoidManualPaymentAction)->execute($payment);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test('voiding a payment records a PaymentVoided activity log entry', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    (new VoidManualPaymentAction)->execute($payment, 'Duplicate entry');

    $log = DashboardActivityLog::where('event_type', 'payment.voided')->first();
    expect($log)->not->toBeNull()
        ->and($log->description)->toContain('Duplicate entry');
});

test('a voided payment can be voided again is rejected', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);
    (new VoidManualPaymentAction)->execute($payment);

    expect(fn () => (new VoidManualPaymentAction)->execute($payment->fresh()))
        ->toThrow(PaymentNotVoidableException::class);
});

test('recording a manual payment logs a PaymentRecorded activity entry', function () {
    $invoice = makePaymentTestInvoice(10000);
    (new RecordManualPaymentAction)->execute($invoice, 4000);

    expect(DashboardActivityLog::where('event_type', 'payment.recorded')->count())->toBe(1);
});

test('the receipts page lets an admin edit and void a payment', function () {
    $invoice = makePaymentTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openEditPayment', $payment->ulid)
        ->assertSet('editPaymentAmount', 40.0)
        ->set('editPaymentAmount', 25)
        ->call('submitEditPayment')
        ->assertHasNoErrors();

    expect($payment->fresh()->amount)->toBe(2500);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openVoidPayment', $payment->ulid)
        ->set('voidReason', 'Test void')
        ->call('submitVoidPayment');

    expect($payment->fresh()->status)->toBe(PaymentRecordStatus::Voided);
});

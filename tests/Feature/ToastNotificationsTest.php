<?php

declare(strict_types=1);

use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Livewire\Invoices\InvoiceIndex;
use App\Livewire\Receipts\ReceiptIndex;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * session()->flash() only renders on the *next full page load* — a
 * Livewire action re-renders just the component's own DOM, never the
 * surrounding layout where the flash-message toast lives, so a flash set
 * during an in-place action (no redirect) silently never appeared. These
 * assert the live 'notify' browser event fires instead, which the layout's
 * listener picks up immediately regardless of whether a redirect follows.
 */
function makeToastTestInvoice(): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-TOAST-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('generating a receipt dispatches a live notify toast', function () {
    $invoice = makeToastTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('generateReceipt', $payment->ulid)
        ->assertDispatched('notify', type: 'success', message: 'Receipt generated. You can download it or send it to the client below.');
});

test('recording a payment dispatches a live notify toast, not just a session flash', function () {
    $invoice = makeToastTestInvoice();

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->call('openRecordPayment', $invoice->ulid)
        ->set('recordPaymentAmount', 100)
        ->call('submitRecordPayment')
        ->assertDispatched('notify');
});

test('sending an invoice dispatches a live notify toast', function () {
    $invoice = makeToastTestInvoice();

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->call('sendInvoice', $invoice->ulid)
        ->assertDispatched('notify', type: 'success', message: "Invoice {$invoice->invoice_number} sent to {$invoice->client->email}.");
});

test('generating a receipt for a payment that already has one dispatches an error notify toast', function () {
    $invoice = makeToastTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);
    app(GenerateInvoiceReceiptAction::class)->execute($payment);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('generateReceipt', $payment->ulid)
        ->assertDispatched('notify', type: 'error');
});

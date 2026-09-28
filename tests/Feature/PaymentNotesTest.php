<?php

declare(strict_types=1);

use App\Actions\EditManualPaymentAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Livewire\Invoices\InvoiceIndex;
use App\Livewire\Receipts\ReceiptIndex;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

function makePaymentNotesTestInvoice(int $totalAmountCents = 10000): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-NOTE-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => $totalAmountCents,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('recording a manual payment can capture notes', function () {
    $invoice = makePaymentNotesTestInvoice();

    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000, null, 'Bank transfer ref #1234');

    expect($payment->notes)->toBe('Bank transfer ref #1234');
});

test('editing a payment can correct its notes', function () {
    $invoice = makePaymentNotesTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000, null, 'Original note');

    (new EditManualPaymentAction)->execute($payment, 4000, null, 'Corrected note');

    expect($payment->fresh()->notes)->toBe('Corrected note');
});

test('editing a payment can clear its notes', function () {
    $invoice = makePaymentNotesTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000, null, 'Will be cleared');

    (new EditManualPaymentAction)->execute($payment, 4000, null, '');

    expect($payment->fresh()->notes)->toBe('');
});

test('the record payment modal saves notes entered by the admin', function () {
    $invoice = makePaymentNotesTestInvoice();

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->call('openRecordPayment', $invoice->ulid)
        ->set('recordPaymentAmount', 40)
        ->set('recordPaymentNotes', 'Paid via bank transfer')
        ->call('submitRecordPayment')
        ->assertHasNoErrors();

    $payment = $invoice->fresh()->payments()->first();
    expect($payment->notes)->toBe('Paid via bank transfer');
});

test('the edit payment modal pre-fills existing notes and saves corrections', function () {
    $invoice = makePaymentNotesTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000, null, 'Initial note');

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openEditPayment', $payment->ulid)
        ->assertSet('editPaymentNotes', 'Initial note')
        ->set('editPaymentNotes', 'Updated note')
        ->call('submitEditPayment')
        ->assertHasNoErrors();

    expect($payment->fresh()->notes)->toBe('Updated note');
});

test('a payment note is visible on the receipts page and included in the activity log', function () {
    $invoice = makePaymentNotesTestInvoice();
    (new RecordManualPaymentAction)->execute($invoice, 4000, null, 'Wire transfer #9876');

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->assertSee('Wire transfer #9876');

    $log = DashboardActivityLog::where('event_type', 'payment.recorded')->first();
    expect($log->description)->toContain('Wire transfer #9876');
});

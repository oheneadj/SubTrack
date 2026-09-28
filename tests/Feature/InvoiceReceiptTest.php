<?php

declare(strict_types=1);

use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Livewire\Invoices\InvoiceIndex;
use App\Livewire\Receipts\ReceiptIndex;
use App\Mail\ReceiptMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function makeReceiptTestInvoice(int $totalAmountCents = 10000): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-RCPT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => $totalAmountCents,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('generating a receipt before any payment is rejected', function () {
    $invoice = makeReceiptTestInvoice();

    expect(fn () => app(GenerateInvoiceReceiptAction::class)->execute($invoice))
        ->toThrow(RuntimeException::class);
});

test('generating a receipt for a partially paid invoice captures the amount paid so far', function () {
    $invoice = makeReceiptTestInvoice(10000);
    (new RecordManualPaymentAction)->execute($invoice, 4000);

    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($invoice->fresh(), 'Partial payment');

    expect($receipt->amount_usd)->toBe(4000)
        ->and($receipt->invoice_id)->toBe($invoice->id)
        ->and($receipt->client_id)->toBe($invoice->client_id)
        ->and($receipt->notes)->toBe('Partial payment')
        ->and($receipt->pdf_path)->not->toBeNull()
        ->and($receipt->source_label)->toBe("Invoice {$invoice->invoice_number}");
});

test('the receipts page scoped to an invoice shows the customer and lets an admin generate a receipt', function () {
    $invoice = makeReceiptTestInvoice(10000);
    (new RecordManualPaymentAction)->execute($invoice, 5000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->assertSee($invoice->invoice_number)
        ->assertSee($invoice->client->name)
        ->call('generateReceipt')
        ->assertHasNoErrors();

    expect($invoice->receipts()->count())->toBe(1);
});

test('sending a receipt queues the receipt mail to the client', function () {
    Mail::fake();

    $invoice = makeReceiptTestInvoice(10000);
    (new RecordManualPaymentAction)->execute($invoice, 10000);
    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($invoice->fresh());

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class)
        ->call('sendReceipt', $receipt->ulid);

    Mail::assertQueued(ReceiptMail::class, fn ($mail) => $mail->receipt->id === $receipt->id);
});

test('the Record Payment button and receipts link are hidden once an invoice is fully paid', function () {
    $invoice = makeReceiptTestInvoice(10000);
    (new RecordManualPaymentAction)->execute($invoice, 10000);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->assertDontSee("openRecordPayment('{$invoice->ulid}')", false)
        ->assertSee('Receipts');
});

test('a Sent invoice with no payment shows Record Payment but not the Receipts link', function () {
    $invoice = makeReceiptTestInvoice(10000);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->assertSee('Record Payment')
        ->assertDontSee('Receipts');
});

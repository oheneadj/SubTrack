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
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\ReceiptPdfService;
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

test('generating a receipt for a payment that has not succeeded is rejected', function () {
    $invoice = makeReceiptTestInvoice();
    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'gateway' => 'manual',
        'method' => 'manual',
        'amount' => 4000,
        'currency' => 'usd',
        'status' => 'pending',
    ]);

    expect(fn () => app(GenerateInvoiceReceiptAction::class)->execute($payment))
        ->toThrow(RuntimeException::class);
});

test('generating a receipt for a payment captures exactly that payment\'s amount, not the invoice total', function () {
    $invoice = makeReceiptTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($payment, 'Partial payment');

    expect($receipt->amount_usd)->toBe(4000)
        ->and($receipt->payment_id)->toBe($payment->id)
        ->and($receipt->invoice_id)->toBe($invoice->id)
        ->and($receipt->client_id)->toBe($invoice->client_id)
        ->and($receipt->notes)->toBe('Partial payment')
        ->and($receipt->pdf_path)->not->toBeNull()
        ->and($receipt->source_label)->toBe("Invoice {$invoice->invoice_number}");
});

test('two separate payments made before either is receipted each get their own receipt, not one merged receipt', function () {
    $invoice = makeReceiptTestInvoice(10000);
    $paymentOne = (new RecordManualPaymentAction)->execute($invoice, 3000);
    $paymentTwo = (new RecordManualPaymentAction)->execute($invoice->fresh(), 4000);

    $receiptOne = app(GenerateInvoiceReceiptAction::class)->execute($paymentOne);
    $receiptTwo = app(GenerateInvoiceReceiptAction::class)->execute($paymentTwo);

    expect($receiptOne->amount_usd)->toBe(3000)
        ->and($receiptTwo->amount_usd)->toBe(4000)
        ->and($invoice->receipts()->count())->toBe(2);
});

test('generating a receipt twice for the same payment is rejected', function () {
    $invoice = makeReceiptTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    app(GenerateInvoiceReceiptAction::class)->execute($payment);

    expect(fn () => app(GenerateInvoiceReceiptAction::class)->execute($payment->fresh()))
        ->toThrow(RuntimeException::class);

    expect(Invoice::find($invoice->id)->receipts()->count())->toBe(1);
});

test('the receipts page marks a receipted payment and offers Generate Receipt for one that is not', function () {
    $invoice = makeReceiptTestInvoice(10000);
    $paymentOne = (new RecordManualPaymentAction)->execute($invoice, 3000);
    (new RecordManualPaymentAction)->execute($invoice->fresh(), 4000);
    app(GenerateInvoiceReceiptAction::class)->execute($paymentOne);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->assertSee('Receipted')
        ->assertSeeHtml('generateReceipt(');
});

test('the receipts page scoped to an invoice shows the customer and lets an admin generate a receipt for a payment', function () {
    $invoice = makeReceiptTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 5000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->assertSee($invoice->invoice_number)
        ->assertSee($invoice->client->name)
        ->call('generateReceipt', $payment->ulid)
        ->assertHasNoErrors();

    expect($invoice->receipts()->count())->toBe(1);
});

test('sending a receipt queues the receipt mail to the client', function () {
    Mail::fake();

    $invoice = makeReceiptTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);
    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);

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

test('viewing a receipt streams it inline while downloading forces a save-as', function () {
    $invoice = makeReceiptTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);
    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);

    $user = User::factory()->create();

    $viewResponse = $this->actingAs($user)->get(route('receipts.view', $receipt));
    expect($viewResponse->headers->get('Content-Disposition'))->toContain('inline');

    $downloadResponse = Livewire::actingAs($user)
        ->test(ReceiptIndex::class)
        ->instance()
        ->downloadReceipt($receipt->ulid, app(ReceiptPdfService::class));
    expect($downloadResponse->headers->get('Content-Disposition'))->toContain('attachment');
});

test('a Sent invoice with no payment shows Record Payment but not the Receipts link', function () {
    $invoice = makeReceiptTestInvoice(10000);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceIndex::class)
        ->assertSee('Record Payment')
        ->assertDontSee('Receipts');
});

test('the receipts page scoped to an unpaid invoice lets an admin record a payment without leaving the page', function () {
    $invoice = makeReceiptTestInvoice(10000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openRecordPayment', $invoice->ulid)
        ->assertSet('recordPaymentAmount', 100.0)
        ->set('recordPaymentAmount', 40)
        ->call('submitRecordPayment')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->amount_paid)->toBe(4000);
});

test('the receipts page rejects a recorded payment over the balance due', function () {
    $invoice = makeReceiptTestInvoice(10000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openRecordPayment', $invoice->ulid)
        ->set('recordPaymentAmount', 500)
        ->call('submitRecordPayment')
        ->assertHasErrors('recordPaymentAmount');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test('the receipts page hides the Record Payment button once the invoice is fully paid', function () {
    $invoice = makeReceiptTestInvoice(10000);
    (new RecordManualPaymentAction)->execute($invoice, 10000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->assertDontSee("openRecordPayment('{$invoice->ulid}')", false);
});

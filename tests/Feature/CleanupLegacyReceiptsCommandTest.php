<?php

declare(strict_types=1);

use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\Subscription;
use Illuminate\Support\Facades\Storage;

function makeCleanupTestInvoice(): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-CLEAN-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('dry-run lists legacy receipts without deleting anything', function () {
    $invoice = makeCleanupTestInvoice();
    $legacyReceipt = Receipt::create([
        'invoice_id' => $invoice->id,
        'client_id' => $invoice->client_id,
        'receipt_number' => 'RCT-LEGACY-'.uniqid(),
        'amount_usd' => 5000,
        'issued_date' => now(),
    ]);

    $this->artisan('receipts:cleanup-legacy')
        ->expectsOutputToContain($legacyReceipt->receipt_number)
        ->expectsOutputToContain('Re-run with --force')
        ->assertExitCode(0);

    expect(Receipt::find($legacyReceipt->id))->not->toBeNull();
});

test('--force with confirmation deletes legacy receipts and their PDF files', function () {
    Storage::fake('public');

    $invoice = makeCleanupTestInvoice();
    Storage::disk('public')->put('receipts/legacy.pdf', 'fake-pdf-bytes');
    $legacyReceipt = Receipt::create([
        'invoice_id' => $invoice->id,
        'client_id' => $invoice->client_id,
        'receipt_number' => 'RCT-LEGACY-'.uniqid(),
        'amount_usd' => 5000,
        'issued_date' => now(),
        'pdf_path' => 'receipts/legacy.pdf',
    ]);

    $this->artisan('receipts:cleanup-legacy', ['--force' => true])
        ->expectsConfirmation('This will PERMANENTLY delete 1 receipt(s) and their PDF files. This cannot be undone. Continue?', 'yes')
        ->assertExitCode(0);

    expect(Receipt::find($legacyReceipt->id))->toBeNull();
    Storage::disk('public')->assertMissing('receipts/legacy.pdf');
});

test('declining the confirmation deletes nothing', function () {
    $invoice = makeCleanupTestInvoice();
    $legacyReceipt = Receipt::create([
        'invoice_id' => $invoice->id,
        'client_id' => $invoice->client_id,
        'receipt_number' => 'RCT-LEGACY-'.uniqid(),
        'amount_usd' => 5000,
        'issued_date' => now(),
    ]);

    $this->artisan('receipts:cleanup-legacy', ['--force' => true])
        ->expectsConfirmation('This will PERMANENTLY delete 1 receipt(s) and their PDF files. This cannot be undone. Continue?', 'no')
        ->assertExitCode(0);

    expect(Receipt::find($legacyReceipt->id))->not->toBeNull();
});

test('a receipt already linked to a payment is left alone', function () {
    $invoice = makeCleanupTestInvoice();
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);
    $linkedReceipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);

    $this->artisan('receipts:cleanup-legacy')
        ->expectsOutputToContain('No legacy receipts found')
        ->assertExitCode(0);

    expect(Receipt::find($linkedReceipt->id))->not->toBeNull();
});

test('a subscription-only receipt (never invoice-linked) is left alone', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'legacy-'.uniqid().'.com',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);
    $subscriptionReceipt = Receipt::create([
        'subscription_id' => $subscription->id,
        'client_id' => $client->id,
        'receipt_number' => 'RCT-SUB-'.uniqid(),
        'amount_usd' => 1000,
        'issued_date' => now(),
    ]);

    $this->artisan('receipts:cleanup-legacy')
        ->expectsOutputToContain('No legacy receipts found')
        ->assertExitCode(0);

    expect(Receipt::find($subscriptionReceipt->id))->not->toBeNull();
});

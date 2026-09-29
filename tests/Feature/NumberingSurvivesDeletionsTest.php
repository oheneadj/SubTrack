<?php

declare(strict_types=1);

use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Receipt;
use App\Services\InvoiceNumberService;
use App\Services\ReceiptNumberService;

/**
 * ReceiptNumberService/InvoiceNumberService used to base the next number on
 * a plain row count, which silently produces an already-taken number the
 * moment any row for the year has been deleted (e.g. via
 * receipts:cleanup-legacy) but another with a *higher* original sequence
 * survives — a real scenario, not a hypothetical: cleaning up legacy
 * receipts removes them in whatever order they're found, not strictly
 * lowest-to-highest.
 */
test('a receipt number is never reused after a lower-numbered receipt is deleted', function () {
    $year = now()->year;
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);

    $r1 = Receipt::create(['client_id' => $client->id, 'receipt_number' => "RCT-{$year}-001", 'amount_usd' => 100, 'issued_date' => now()]);
    Receipt::create(['client_id' => $client->id, 'receipt_number' => "RCT-{$year}-002", 'amount_usd' => 100, 'issued_date' => now()]);
    $r3 = Receipt::create(['client_id' => $client->id, 'receipt_number' => "RCT-{$year}-003", 'amount_usd' => 100, 'issued_date' => now()]);

    // Delete receipt #1 — a lower sequence number than the survivor #3.
    // A count-based generator would now think only 2 receipts exist and
    // hand out "003" again, colliding with the still-existing $r3.
    $r1->delete();

    $next = app(ReceiptNumberService::class)->generate();

    expect($next)->toBe("RCT-{$year}-004")
        ->and($next)->not->toBe($r3->receipt_number);
});

test('an invoice number is never reused after a lower-numbered invoice is soft-deleted', function () {
    $year = now()->year;
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);

    $i1 = Invoice::create(['client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => "INV-{$year}-001", 'issued_date' => now(), 'due_date' => now()->addDays(14), 'total_amount' => 1000, 'status' => 'Draft']);
    Invoice::create(['client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => "INV-{$year}-002", 'issued_date' => now(), 'due_date' => now()->addDays(14), 'total_amount' => 1000, 'status' => 'Draft']);
    $i3 = Invoice::create(['client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => "INV-{$year}-003", 'issued_date' => now(), 'due_date' => now()->addDays(14), 'total_amount' => 1000, 'status' => 'Draft']);

    $i1->delete(); // soft delete

    $next = app(InvoiceNumberService::class)->generate();

    expect($next)->toBe("INV-{$year}-004")
        ->and($next)->not->toBe($i3->invoice_number);
});

test('generating a receipt after cleaning up legacy receipts never collides with a surviving one', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-COLLIDE-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);
    $survivingReceipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);

    // A legacy receipt with a *lower* number than the surviving one above,
    // simulating the real ordering the cleanup command encounters.
    $year = now()->year;
    $legacyNumber = 'RCT-'.$year.'-'.str_pad((string) (((int) substr($survivingReceipt->receipt_number, -3)) - 1), 3, '0', STR_PAD_LEFT);
    Receipt::create(['invoice_id' => $invoice->id, 'client_id' => $client->id, 'receipt_number' => $legacyNumber, 'amount_usd' => 500, 'issued_date' => now()]);

    // Cleanup removes the legacy one, leaving a gap below the surviving receipt.
    $this->artisan('receipts:cleanup-legacy', ['--force' => true])->expectsConfirmation(
        'This will PERMANENTLY delete 1 receipt(s) and their PDF files. This cannot be undone. Continue?',
        'yes'
    );

    // A second payment, receipted afterward, must not collide with the surviving receipt.
    $payment2 = (new RecordManualPaymentAction)->execute($invoice->fresh(), 6000);
    $secondReceipt = app(GenerateInvoiceReceiptAction::class)->execute($payment2);

    expect($secondReceipt->receipt_number)->not->toBe($survivingReceipt->receipt_number);
    expect(Receipt::where('receipt_number', $secondReceipt->receipt_number)->count())->toBe(1);
});

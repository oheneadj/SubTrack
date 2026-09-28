<?php

declare(strict_types=1);

use App\Actions\GenerateInvoiceReceiptAction;
use App\Enums\InvoiceStatus;
use App\Livewire\Receipts\ReceiptIndex;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * Reproduces the exact scenario reported: making two separate payments
 * before generating any receipt must never merge them into one receipt
 * for their combined total — each payment is its own transaction and
 * needs its own receipt.
 */
test('recording two separate payments before generating any receipt lets each be receipted individually, not merged', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-SEP-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
    $user = User::factory()->create();

    // Make two separate payments — neither receipted yet.
    Livewire::actingAs($user)
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openRecordPayment', $invoice->ulid)
        ->set('recordPaymentAmount', 30)
        ->call('submitRecordPayment')
        ->assertHasNoErrors();

    Livewire::actingAs($user)
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->call('openRecordPayment', $invoice->ulid)
        ->set('recordPaymentAmount', 70)
        ->call('submitRecordPayment')
        ->assertHasNoErrors();

    $payments = $invoice->fresh()->payments()->orderBy('amount')->get();
    expect($payments)->toHaveCount(2)
        ->and($payments[0]->amount)->toBe(3000)
        ->and($payments[1]->amount)->toBe(7000);

    // Generate a receipt for each — must produce two receipts, not one
    // merged receipt for the full $100.
    $firstReceipt = app(GenerateInvoiceReceiptAction::class)->execute($payments[0]);
    $secondReceipt = app(GenerateInvoiceReceiptAction::class)->execute($payments[1]);

    expect($firstReceipt->amount_usd)->toBe(3000)
        ->and($secondReceipt->amount_usd)->toBe(7000)
        ->and($firstReceipt->id)->not->toBe($secondReceipt->id)
        ->and($invoice->receipts()->count())->toBe(2);

    // Both show up correctly on the Receipts page.
    Livewire::actingAs($user)
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->assertSee($firstReceipt->receipt_number)
        ->assertSee($secondReceipt->receipt_number)
        ->assertSee('$30.00')
        ->assertSee('$70.00');
});

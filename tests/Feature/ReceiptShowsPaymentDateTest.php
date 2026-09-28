<?php

declare(strict_types=1);

use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Livewire\Receipts\ReceiptIndex;
use App\Mail\ReceiptMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

test('the receipt PDF shows the actual payment date, not just when the receipt was issued', function () {
    Setting::set('company_name', 'Acme Web Services');

    $client = Client::create(['name' => 'Client Co', 'email' => 'client-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-PD-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
    $paidAt = CarbonImmutable::now()->subDays(5);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000, $paidAt);
    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);
    $receipt->load(['client', 'subscription', 'invoice', 'payment']);
    $settings = Setting::getAllAsArray();

    $html = view('pdf.receipt', compact('receipt', 'settings'))->render();

    expect($html)->toContain('Payment Date')
        ->and($html)->toContain($paidAt->format('F d, Y'));
});

test('the receipt email shows the actual payment date', function () {
    $client = Client::create(['name' => 'Client Co', 'email' => 'client-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-PD2-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
    $paidAt = CarbonImmutable::now()->subDays(3);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000, $paidAt);
    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);
    $receipt->load('payment');

    $html = (new ReceiptMail($receipt))->render();

    expect($html)->toContain('Payment Date')
        ->and($html)->toContain($paidAt->format('F d, Y'));
});

test('the receipts page table shows the payment date column', function () {
    $client = Client::create(['name' => 'Client Co', 'email' => 'client-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-PD3-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
    $paidAt = CarbonImmutable::now()->subDays(7);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000, $paidAt);
    app(GenerateInvoiceReceiptAction::class)->execute($payment);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class)
        ->assertSee($paidAt->format('M d, Y'));
});

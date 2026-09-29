<?php

declare(strict_types=1);

use App\Enums\ActivityEventType;
use App\Enums\InvoiceStatus;
use App\Livewire\Dashboard\OverviewDashboard;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

function makeOverdueTestInvoice(InvoiceStatus $status, int $dueDaysAgo): Invoice
{
    $client = Client::factory()->create();

    return Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-OVERDUE-'.uniqid(),
        'issued_date' => now()->subDays($dueDaysAgo + 14),
        'due_date' => now()->subDays($dueDaysAgo),
        'total_amount' => 10000,
        'status' => $status,
    ]);
}

test('a Sent invoice past its due date is flagged Overdue', function () {
    $invoice = makeOverdueTestInvoice(InvoiceStatus::Sent, 5);

    Artisan::call('subtrack:check-invoice-overdue');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
});

test('a Sent invoice not yet due is left alone', function () {
    $invoice = makeOverdueTestInvoice(InvoiceStatus::Sent, -5);

    Artisan::call('subtrack:check-invoice-overdue');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test('a Draft invoice past due date is not flagged — it was never actually sent', function () {
    $invoice = makeOverdueTestInvoice(InvoiceStatus::Draft, 5);

    Artisan::call('subtrack:check-invoice-overdue');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Draft);
});

test('a Partially Paid invoice past due date keeps its status — that information is not destroyed', function () {
    $invoice = makeOverdueTestInvoice(InvoiceStatus::PartiallyPaid, 5);

    Artisan::call('subtrack:check-invoice-overdue');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PartiallyPaid);
});

test('a Paid invoice past due date is left alone', function () {
    $invoice = makeOverdueTestInvoice(InvoiceStatus::Paid, 5);

    Artisan::call('subtrack:check-invoice-overdue');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('flagging an invoice Overdue records an activity log entry', function () {
    makeOverdueTestInvoice(InvoiceStatus::Sent, 5);

    Artisan::call('subtrack:check-invoice-overdue');

    expect(DashboardActivityLog::where('event_type', ActivityEventType::InvoiceOverdue)->count())->toBe(1);
});

test('this command is what actually makes the Home dashboard Overdue stat non-zero', function () {
    makeOverdueTestInvoice(InvoiceStatus::Sent, 5);

    $user = User::factory()->create();
    $before = Livewire::actingAs($user)->test(OverviewDashboard::class)->get('stats');
    expect($before['overdue'])->toBe(0);

    Artisan::call('subtrack:check-invoice-overdue');

    $after = Livewire::actingAs($user)->test(OverviewDashboard::class)->get('stats');
    expect($after['overdue'])->toBe(1);
});

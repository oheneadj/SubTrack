<?php

declare(strict_types=1);

use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Actions\VoidManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Exceptions\VoidReasonRequiredException;
use App\Livewire\Receipts\ReceiptIndex;
use App\Livewire\Settings\AppSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Livewire\Livewire;

function makeSettingsTestInvoice(int $totalAmountCents = 10000): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-SET-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => $totalAmountCents,
        'status' => InvoiceStatus::Sent,
    ]);
}

test('receipt numbers use the configured prefix', function () {
    Setting::set('receipt_prefix', 'REC');

    $invoice = makeSettingsTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);
    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);

    expect($receipt->receipt_number)->toStartWith('REC-'.now()->year.'-');
});

test('receipt numbers default to RCT when no prefix is configured', function () {
    $invoice = makeSettingsTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 10000);
    $receipt = app(GenerateInvoiceReceiptAction::class)->execute($payment);

    expect($receipt->receipt_number)->toStartWith('RCT-'.now()->year.'-');
});

test('invoice numbers use the configured prefix', function () {
    Setting::set('invoice_prefix', 'BILL');

    expect(app(InvoiceNumberService::class)->generate())->toStartWith('BILL-'.now()->year.'-');
});

test('a payment recorded within the configured edit window is editable, outside it is not', function () {
    Setting::set('payment_edit_window_hours', '2');

    $invoice = makeSettingsTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    expect($payment->isEditable())->toBeTrue();

    $payment->created_at = now()->subHours(3);
    $payment->save();

    expect($payment->fresh()->isEditable())->toBeFalse();
});

test('voiding without a reason is rejected once require_void_reason is enabled', function () {
    Setting::set('require_void_reason', '1');

    $invoice = makeSettingsTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    expect(fn () => app(VoidManualPaymentAction::class)->execute($payment))
        ->toThrow(VoidReasonRequiredException::class);

    app(VoidManualPaymentAction::class)->execute($payment, 'Entered wrong amount');
    expect($payment->fresh()->status->value)->toBe('voided');
});

test('voiding without a reason is allowed when require_void_reason is off', function () {
    $invoice = makeSettingsTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    app(VoidManualPaymentAction::class)->execute($payment);

    expect($payment->fresh()->status->value)->toBe('voided');
});

test('the void payment modal surfaces the required-reason validation error', function () {
    Setting::set('require_void_reason', '1');

    $invoice = makeSettingsTestInvoice(10000);
    $payment = (new RecordManualPaymentAction)->execute($invoice, 4000);

    Livewire::actingAs(User::factory()->create())
        ->test(ReceiptIndex::class, ['invoice' => $invoice->ulid])
        ->assertSet('voidReasonRequired', true)
        ->call('openVoidPayment', $payment->ulid)
        ->call('submitVoidPayment')
        ->assertHasErrors('voidReason');

    expect($payment->fresh()->status->value)->toBe('succeeded');
});

test('the admin settings page saves the new receipt prefix, edit window, and void reason settings', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(AppSettings::class)
        ->set('appName', 'SubTrack')
        ->set('companyName', 'Acme Co')
        ->set('contactEmail', 'billing@acme.test')
        ->set('receiptPrefix', 'REC')
        ->set('paymentEditWindowHours', 6)
        ->set('requireVoidReason', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('receipt_prefix'))->toBe('REC')
        ->and((int) Setting::get('payment_edit_window_hours'))->toBe(6)
        ->and(Setting::get('require_void_reason'))->toBe('1');
});

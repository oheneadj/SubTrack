<?php

declare(strict_types=1);

use App\Actions\InitiatePayment;
use App\Contracts\PaymentGateway;
use App\Enums\InvoiceStatus;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Http\Request;

function makeInitiatePaymentInvoice(int $totalAmountCents = 10000): Invoice
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    return Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-INIT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => $totalAmountCents,
        'status' => InvoiceStatus::Sent,
    ]);
}

/** Records the amount it was asked to charge, without calling any real provider. */
function fakeGateway(): PaymentGateway
{
    return new class implements PaymentGateway
    {
        public ?int $chargedAmount = null;

        public function label(): string
        {
            return 'Fake Gateway';
        }

        public function slug(): string
        {
            return 'fake';
        }

        public function method(): string
        {
            return 'card';
        }

        public function createCheckout(Invoice $invoice, string $returnUrl, int $amountCents): string
        {
            $this->chargedAmount = $amountCents;

            return 'https://example.test/checkout';
        }

        public function verifyWebhook(Request $request): void {}

        public function handleWebhook(Request $request): void {}

        public function webhookEventId(Request $request): ?string
        {
            return null;
        }

        public function pollStatus(Payment $payment): void {}
    };
}

test('defaults to charging the full remaining balance when no amount is given', function () {
    $invoice = makeInitiatePaymentInvoice(10000);
    $gateway = fakeGateway();

    (new InitiatePayment)->execute($invoice, $gateway, 'https://example.test/return');

    expect($gateway->chargedAmount)->toBe(10000);
    expect(Payment::where('invoice_id', $invoice->id)->first()->amount)->toBe(10000);
});

test('charges only the requested partial amount when one is given', function () {
    $invoice = makeInitiatePaymentInvoice(10000);
    $gateway = fakeGateway();

    (new InitiatePayment)->execute($invoice, $gateway, 'https://example.test/return', 2500);

    expect($gateway->chargedAmount)->toBe(2500);
    expect(Payment::where('invoice_id', $invoice->id)->first()->amount)->toBe(2500);
});

test('rejects a checkout amount larger than the balance due', function () {
    $invoice = makeInitiatePaymentInvoice(10000);

    expect(fn () => (new InitiatePayment)->execute($invoice, fakeGateway(), 'https://example.test/return', 10001))
        ->toThrow(InvalidPaymentAmountException::class);
});

test('rejects starting checkout on an already fully paid invoice', function () {
    $invoice = makeInitiatePaymentInvoice(10000);
    $invoice->update(['status' => InvoiceStatus::Paid]);

    expect(fn () => (new InitiatePayment)->execute($invoice, fakeGateway(), 'https://example.test/return'))
        ->toThrow(InvoiceAlreadyPaidException::class);
});

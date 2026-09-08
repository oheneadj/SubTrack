<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Provider;

test('a soft-deleted provider name is mutated so a new provider can reuse it', function () {
    $provider = Provider::create(['name' => 'TestHost Registrar']);
    $provider->delete();

    expect($provider->fresh()->name)->toBe("TestHost Registrar-deleted-{$provider->id}");

    $newProvider = Provider::create(['name' => 'TestHost Registrar']);

    expect($newProvider->name)->toBe('TestHost Registrar');
});

test('a soft-deleted invoice number is mutated so a new invoice can reuse it', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'billing@acme.test']);

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
    ]);
    $invoice->delete();

    expect($invoice->fresh()->invoice_number)->toBe("INV-0001-deleted-{$invoice->id}");

    $newInvoice = Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
    ]);

    expect($newInvoice->invoice_number)->toBe('INV-0001');
});

<?php

declare(strict_types=1);

use App\Actions\GenerateReceiptAction;
use App\Mail\ReceiptMail;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('generating a receipt creates the record, the PDF, and queues the mail', function () {
    Storage::fake('local');
    Mail::fake();

    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    $receipt = app(GenerateReceiptAction::class)->execute($subscription, 1500, 'Paid via bank transfer');

    expect($receipt->exists)->toBeTrue();
    expect($receipt->amount_usd)->toBe(1500);
    expect($receipt->client_id)->toBe($client->id);
    expect($receipt->receipt_number)->toStartWith('RCT-'.now()->year.'-');
    expect($receipt->pdf_path)->not->toBeNull();

    Storage::disk('local')->assertExists('public/'.$receipt->pdf_path);
    Mail::assertQueued(ReceiptMail::class, fn ($mail) => $mail->receipt->is($receipt));
});

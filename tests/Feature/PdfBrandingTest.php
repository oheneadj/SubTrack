<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

test('the invoice PDF shows the configured company name, not the app name', function () {
    Setting::set('company_name', 'Acme Web Services');

    $client = Client::create(['name' => 'Client Co', 'email' => 'client@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-BRAND-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 1000,
        'status' => 'Draft',
    ]);
    $invoice->load(['client', 'items']);
    $settings = Setting::getAllAsArray();

    $html = view('pdf.invoice', compact('invoice', 'settings'))->render();

    expect($html)->toContain('Acme Web Services')
        ->and($html)->not->toContain(config('app.name', 'SubTrack'));
});

test('the receipt PDF shows the configured company name, not the app name', function () {
    Setting::set('company_name', 'Acme Web Services');

    $client = Client::create(['name' => 'Client Co', 'email' => 'client@test.test']);
    $receipt = Receipt::create([
        'client_id' => $client->id,
        'receipt_number' => 'RCT-BRAND-'.uniqid(),
        'amount_usd' => 1000,
        'issued_date' => now(),
    ]);
    $receipt->load(['client', 'subscription', 'invoice']);
    $settings = Setting::getAllAsArray();

    $html = view('pdf.receipt', compact('receipt', 'settings'))->render();

    expect($html)->toContain('Acme Web Services')
        ->and($html)->not->toContain(config('app.name', 'SubTrack'));
});

test('the invoice PDF embeds the configured logo image instead of a text company name when one is set', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logos/test-logo.png', 'fake-image-bytes');
    Setting::set('logo_path', 'logos/test-logo.png');
    Setting::set('company_name', 'Acme Web Services');

    $client = Client::create(['name' => 'Client Co', 'email' => 'client@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-LOGO-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 1000,
        'status' => 'Draft',
    ]);
    $invoice->load(['client', 'items']);
    $settings = Setting::getAllAsArray();

    $html = view('pdf.invoice', compact('invoice', 'settings'))->render();

    expect($html)->toContain('<img src=')
        ->and($html)->toContain('test-logo.png');
});

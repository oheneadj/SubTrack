<?php

declare(strict_types=1);

use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('recent payments includes a partially paid invoice, showing only the amount actually received', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-FIN-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
    (new RecordManualPaymentAction)->execute($invoice, 4000);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    $recentPayments = $response->viewData('recentPayments');
    $entry = $recentPayments->firstWhere('reference', $invoice->invoice_number);

    expect($entry)->not->toBeNull()
        ->and((float) $entry->amount)->toBe(40.0);
});

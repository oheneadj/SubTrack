<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Livewire\Dashboard\OverviewDashboard;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

test('Home dashboard shows only minimal finance context, not the full duplicated breakdown', function () {
    $response = Livewire::actingAs(User::factory()->create())
        ->test(OverviewDashboard::class);

    // Kept: quick context tiles and a link through to the real detail page.
    $response->assertSee('Total Revenue')
        ->assertSee('Outstanding')
        ->assertSee('View Finance Dashboard for full details');

    // Removed: this dashboard used to duplicate the entire Finance
    // breakdown (chart, MRR, provider costs) — the exact source of the
    // "two dashboards silently drift apart" bug class fixed this session.
    $response->assertDontSee('Revenue vs. Expenses')
        ->assertDontSee('Monthly Revenue')
        ->assertDontSee('Provider Costs');
});

test('Home dashboard finance tiles show compact K formatting for large amounts', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-HOME-COMPACT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 250000, // $2,500.00 paid
        'amount_paid' => 250000,
        'status' => InvoiceStatus::Paid,
    ]);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(OverviewDashboard::class);

    $response->assertSee('$2.5K');
});

<?php

declare(strict_types=1);

use App\Livewire\Dashboard\OverviewDashboard;
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
        ->assertDontSee('Avg. Monthly Revenue')
        ->assertDontSee('Annual Costs');
});

<?php

declare(strict_types=1);

use App\Livewire\Invoices\InvoiceBuilder;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

function makeInvoiceBuilderSubscription(Project $project, Provider $provider, array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'project_id' => $project->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'example-'.uniqid().'.com',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 5000,
        'markup_percentage' => 10,
        'status' => 'Active',
    ], $overrides));
}

test('the project subscriptions list only shows once a project is selected', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    makeInvoiceBuilderSubscription($project, $provider);

    $component = Livewire::actingAs(User::factory()->create())->test(InvoiceBuilder::class);

    expect($component->get('projectSubscriptions'))->toBeEmpty();

    $component->set('client_id', $client->id)->set('project_id', $project->id);

    expect($component->get('projectSubscriptions'))->toHaveCount(1);
});

test('adding a subscription as a line item pre-fills the description and marked-up renewal cost', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = makeInvoiceBuilderSubscription($project, $provider, ['domain_name' => 'acme.com']);

    $component = Livewire::actingAs(User::factory()->create())
        ->test(InvoiceBuilder::class)
        ->set('client_id', $client->id)
        ->set('project_id', $project->id)
        ->call('addSubscriptionItem', $subscription->id);

    $items = $component->get('items');
    $addedItem = collect($items)->firstWhere('subscription_id', $subscription->id);

    expect($addedItem)->not->toBeNull();
    expect($addedItem['description'])->toBe('Renewal: acme.com');
    // $50 renewal + 10% markup = $55.
    expect((float) $addedItem['unit_price'])->toBe(55.0);

    expect($component->get('addedSubscriptionIds'))->toContain($subscription->id);
});

test('saving an invoice persists the subscription_id on the line item', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = makeInvoiceBuilderSubscription($project, $provider);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceBuilder::class)
        ->set('client_id', $client->id)
        ->set('project_id', $project->id)
        ->call('addSubscriptionItem', $subscription->id)
        ->call('removeItem', 0) // drop the blank item added by mount()
        ->call('save');

    $invoice = Invoice::where('client_id', $client->id)->firstOrFail();
    $item = InvoiceItem::where('invoice_id', $invoice->id)->firstOrFail();

    expect($item->subscription_id)->toBe($subscription->id);
});

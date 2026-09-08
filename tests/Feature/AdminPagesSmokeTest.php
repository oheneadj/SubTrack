<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\ActivityLogs\ActivityLogIndex;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Clients\ClientShow;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Livewire\Dashboard\OverviewDashboard;
use App\Livewire\Invoices\InvoiceBuilder;
use App\Livewire\Invoices\InvoiceIndex;
use App\Livewire\MailTemplates\DirectMailer;
use App\Livewire\MailTemplates\MailTemplateIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Providers\ProviderIndex;
use App\Livewire\Providers\ProviderShow;
use App\Livewire\Renewals\RenewalTracker;
use App\Livewire\Settings\AppSettings;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Livewire\Subscriptions\SubscriptionIndex;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserShow;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\MailTemplate;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

/**
 * Broad smoke coverage for every admin-app page — most of these had zero
 * test coverage before, so a page-level render check is the cheapest way
 * to catch a broken Blade/component tag across the whole app at once.
 */
test('every admin page renders without a Blade/component error', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $client = Client::create(['name' => 'Render Check Co', 'email' => 'render@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Render Project']);
    $provider = Provider::create(['name' => 'Render Provider']);
    Subscription::create([
        'client_id' => $client->id, 'project_id' => $project->id, 'provider_id' => $provider->id,
        'service_type' => 'Domain', 'renewal_type' => 'RecurringAnnually', 'domain_name' => 'render.com',
        'purchase_date' => now(), 'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000, 'renewal_cost_usd' => 1000, 'status' => 'Active',
    ]);
    $invoice = Invoice::create([
        'client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => 'RC-0001',
        'issued_date' => now(), 'due_date' => now()->addDays(14),
    ]);
    MailTemplate::create(['name' => 'Render Template', 'slug' => 'render-template', 'subject' => 'Hi', 'body' => 'Body']);
    Setting::query()->create(['key' => 'company_name', 'value' => 'Render Co']);

    Livewire::actingAs($admin)->test(OverviewDashboard::class)->assertOk();
    Livewire::actingAs($admin)->test(FinanceDashboard::class)->assertOk();
    Livewire::actingAs($admin)->test(ClientIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(ClientShow::class, ['client' => $client])->assertOk();
    Livewire::actingAs($admin)->test(ProjectIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(ProjectShow::class, ['project' => $project])->assertOk();
    Livewire::actingAs($admin)->test(ProviderIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(ProviderShow::class, ['provider' => $provider])->assertOk();
    Livewire::actingAs($admin)->test(InvoiceIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(InvoiceBuilder::class)->assertOk();
    Livewire::actingAs($admin)->test(InvoiceBuilder::class, ['invoice' => $invoice])->assertOk();
    Livewire::actingAs($admin)->test(UserIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(UserShow::class, ['user' => $admin])->assertOk();
    Livewire::actingAs($admin)->test(ActivityLogIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(MailTemplateIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(DirectMailer::class)->assertOk();
    Livewire::actingAs($admin)->test(AppSettings::class)->assertOk();
    Livewire::actingAs($admin)->test(Profile::class)->assertOk();
    Livewire::actingAs($admin)->test(Security::class)->assertOk();
    Livewire::actingAs($admin)->test(RenewalTracker::class)->assertOk();
    Livewire::actingAs($admin)->test(SubscriptionIndex::class)->assertOk();
});

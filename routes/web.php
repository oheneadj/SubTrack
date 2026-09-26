<?php

use App\Enums\ServiceType;
use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\EnsureClientAuthenticated;
use App\Livewire\ActivityLogs\ActivityLogIndex;
use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Client\ClientInvoicePortal;
use App\Livewire\Client\ClientLoginPage;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Clients\ClientShow;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Livewire\Dashboard\OverviewDashboard;
use App\Livewire\Dev\ComponentsPreview;
use App\Livewire\Invoices\InvoiceBuilder;
use App\Livewire\Invoices\InvoiceIndex;
use App\Livewire\MailTemplates\DirectMailer;
use App\Livewire\MailTemplates\MailTemplateIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Providers\ProviderIndex;
use App\Livewire\Providers\ProviderShow;
use App\Livewire\Public\PublicInvoicePage;
use App\Livewire\Renewals\RenewalTracker;
use App\Livewire\Settings\AppSettings;
use App\Livewire\Subscriptions\SubscriptionForm;
use App\Livewire\Subscriptions\SubscriptionIndex;
use App\Livewire\Subscriptions\SubscriptionShow;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserShow;
use App\Mail\InvoiceMail;
use App\Mail\SubscriptionReminderMail;
use App\Mail\UserInviteMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('password/force-change', ForcePasswordChange::class)->name('password.force-change');
});

Route::middleware(['auth', 'verified', 'password_change'])->group(function () {
    Route::get('dashboard', OverviewDashboard::class)->name('dashboard');
    Route::get('finances', FinanceDashboard::class)->name('finances.index');
    Route::get('/components-preview', ComponentsPreview::class)->name('components-preview');

    // Phase 4: Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', ClientIndex::class)->name('index');
        Route::get('/{client}', ClientShow::class)->name('show');
    });
    // Phase 4: Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', ProjectIndex::class)->name('index');
        Route::get('/{project}', ProjectShow::class)->name('show');
    });
    // Subscriptions
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', SubscriptionIndex::class)->name('index');
        Route::get('/create', SubscriptionForm::class)->name('create');
        Route::get('/{subscription}', SubscriptionShow::class)->name('show');
        Route::get('/{subscription}/edit', SubscriptionForm::class)->name('edit');
    });

    // Providers
    Route::prefix('providers')->name('providers.')->group(function () {
        Route::get('/', ProviderIndex::class)->name('index');
        Route::get('/{provider}', ProviderShow::class)->name('show');
    });

    Route::get('renewals', RenewalTracker::class)->name('renewals.index');
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', InvoiceIndex::class)->name('index');
        Route::get('/create', InvoiceBuilder::class)->name('create');
        Route::get('/{invoice}', InvoiceBuilder::class)->name('edit');
    });
    Route::get('users', UserIndex::class)->name('users.index')->middleware('super_admin');
    Route::get('users/{user}', UserShow::class)->name('users.show')->middleware('super_admin');
    Route::get('activity-logs', ActivityLogIndex::class)->name('activity-logs.index')->middleware('super_admin');
    Route::get('mail-templates', MailTemplateIndex::class)->name('mail-templates.index')->middleware('super_admin');

    Route::get('mail-templates/{slug}/preview', function ($slug) {
        $user = auth()->user();

        return match ($slug) {
            'user-invite' => new UserInviteMail($user->name, $user->email, 'p4ssw0rd!', route('dashboard')),
            'subscription-reminder' => new SubscriptionReminderMail(Subscription::with(['project.client', 'provider'])->first() ?? (function () {
                $sub = new Subscription(['domain_name' => 'example.com', 'service_type' => ServiceType::Domain, 'expiry_date' => now()->addDays(7)]);
                $project = new Project(['project_name' => 'Demo Project']);
                $project->setRelation('client', new Client(['name' => 'Demo Client']));
                $sub->setRelation('project', $project);
                $sub->setRelation('provider', new Provider(['name' => 'Demo Provider']));

                return $sub;
            })()),
            'invoice-mail' => new InvoiceMail(Invoice::with(['client', 'project'])->first() ?? (function () {
                $invoice = new Invoice(['invoice_number' => 'INV-TEST-001', 'due_date' => now()->addDays(14), 'total_amount' => 1250.00]);
                $invoice->setRelation('client', new Client(['name' => 'Test Client']));
                $invoice->setRelation('project', new Project(['project_name' => 'Test Project']));

                return $invoice;
            })()),
            default => abort(404),
        };
    })->name('mail-templates.preview')->middleware('super_admin');

    Route::get('mail-mailer', DirectMailer::class)->name('mail-mailer.index')->middleware('super_admin');
    Route::get('settings', AppSettings::class)->name('settings.index');
});

/*
|--------------------------------------------------------------------------
| Public Payment Routes (no auth)
|--------------------------------------------------------------------------
*/
Route::get('/pay/{invoice:ulid}', PublicInvoicePage::class)->name('invoice.pay');
Route::post('/pay/{invoice:ulid}/checkout/{gateway}', [InvoicePaymentController::class, 'checkout'])->name('invoice.checkout');

/*
|--------------------------------------------------------------------------
| Payment Gateway Webhooks (no auth, no CSRF — see VerifyCsrfToken)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/{gateway}', WebhookController::class)->name('webhooks.handle');

/*
|--------------------------------------------------------------------------
| Client Portal
|--------------------------------------------------------------------------
*/
Route::get('/client/login', ClientLoginPage::class)->name('client.login');
Route::post('/client/login/send', [ClientAuthController::class, 'sendLink'])->name('client.magic-link.send');
Route::get('/client/auth/{token}', [ClientAuthController::class, 'authenticate'])->name('client.auth');
Route::middleware(EnsureClientAuthenticated::class)->group(function () {
    Route::get('/client/invoices', ClientInvoicePortal::class)->name('client.invoices');
    Route::get('/client/logout', [ClientAuthController::class, 'logout'])->name('client.logout');
});

require __DIR__.'/settings.php';

<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', \App\Livewire\Dashboard\OverviewDashboard::class)->name('dashboard');
    Route::get('/components-preview', \App\Livewire\Dev\ComponentsPreview::class)->name('components-preview');
    
    // Phase 4: Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/',              \App\Livewire\Clients\ClientIndex::class)->name('index');
        Route::get('/{client}',      \App\Livewire\Clients\ClientShow::class)->name('show');
    });
    // Phase 4: Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/',              \App\Livewire\Projects\ProjectIndex::class)->name('index');
        Route::get('/{project}',     \App\Livewire\Projects\ProjectShow::class)->name('show');
    });
    // Subscriptions
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/',               \App\Livewire\Subscriptions\SubscriptionIndex::class)->name('index');
        Route::get('/create',          \App\Livewire\Subscriptions\SubscriptionForm::class)->name('create');
        Route::get('/{subscription}', \App\Livewire\Subscriptions\SubscriptionForm::class)->name('edit');
    });

    // Providers
    Route::prefix('providers')->name('providers.')->group(function () {
        Route::get('/',              \App\Livewire\Providers\ProviderIndex::class)->name('index');
        Route::get('/{provider}',    \App\Livewire\Providers\ProviderShow::class)->name('show');
    });

    Route::get('renewals', \App\Livewire\Renewals\RenewalTracker::class)->name('renewals.index');
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/',              \App\Livewire\Invoices\InvoiceIndex::class)->name('index');
        Route::get('/create',        \App\Livewire\Invoices\InvoiceBuilder::class)->name('create');
        Route::get('/{invoice}',     \App\Livewire\Invoices\InvoiceBuilder::class)->name('edit');
    });
    Route::get('users', \App\Livewire\Users\UserIndex::class)->name('users.index')->middleware('super_admin');
    Route::get('users/{user}', \App\Livewire\Users\UserShow::class)->name('users.show')->middleware('super_admin');
    Route::get('activity-logs', \App\Livewire\ActivityLogs\ActivityLogIndex::class)->name('activity-logs.index')->middleware('super_admin');
    Route::get('mail-templates', \App\Livewire\MailTemplates\MailTemplateIndex::class)->name('mail-templates.index')->middleware('super_admin');
    Route::get('mail-mailer', \App\Livewire\MailTemplates\DirectMailer::class)->name('mail-mailer.index')->middleware('super_admin');
    Route::get('settings',      \App\Livewire\Settings\AppSettings::class)->name('settings.index');
});

require __DIR__.'/settings.php';

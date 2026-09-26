<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\MailTemplates\MailTemplateIndex;
use App\Mail\InvoiceMail;
use App\Mail\SubscriptionReminderMail;
use App\Mail\UserInviteMail;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('mail-templates.index'))->assertRedirect(route('login'));
});

test('non super admins cannot access mail templates', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->get(route('mail-templates.index'))
        ->assertForbidden();
});

test('super admins can view mail templates', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    MailTemplate::factory()->create();

    $this->actingAs($admin)
        ->get(route('mail-templates.index'))
        ->assertOk();
});

test('editing a template loads its subject and body into the form', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $template = MailTemplate::factory()->create([
        'subject' => 'Original subject',
        'body' => 'Original body',
    ]);

    Livewire::actingAs($admin)
        ->test(MailTemplateIndex::class)
        ->call('edit', $template->ulid)
        ->assertSet('editingTemplate.id', $template->id)
        ->assertSet('editSubject', 'Original subject')
        ->assertSet('editBody', 'Original body')
        ->assertSet('showEditModal', true);
});

test('saving updates the template and requires subject and body', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $template = MailTemplate::factory()->create();

    Livewire::actingAs($admin)
        ->test(MailTemplateIndex::class)
        ->call('edit', $template->ulid)
        ->set('editSubject', '')
        ->set('editBody', '')
        ->call('save')
        ->assertHasErrors(['editSubject', 'editBody']);

    Livewire::actingAs($admin)
        ->test(MailTemplateIndex::class)
        ->call('edit', $template->ulid)
        ->set('editSubject', 'Updated subject')
        ->set('editBody', 'Updated body')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    expect($template->fresh()->subject)->toBe('Updated subject');
    expect($template->fresh()->body)->toBe('Updated body');
});

test('sending a test email for a known template slug sends mail to the current user', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $template = MailTemplate::where('slug', 'user-invite')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(MailTemplateIndex::class)
        ->call('sendTest', $template->ulid);

    Mail::assertSent(UserInviteMail::class);
});

test('test sends for templates with no matching record fall back to mock data without error', function () {
    // These mailables implement ShouldQueue; sendTest() must use sendNow() rather
    // than send() (which would force-queue and try to serialize the unsaved mock
    // Subscription/Invoice models built below, which have no primary key).
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $reminderTemplate = MailTemplate::where('slug', 'subscription-reminder')->firstOrFail();
    $invoiceTemplate = MailTemplate::where('slug', 'invoice-mail')->firstOrFail();

    Livewire::actingAs($admin)->test(MailTemplateIndex::class)->call('sendTest', $reminderTemplate->ulid);
    Livewire::actingAs($admin)->test(MailTemplateIndex::class)->call('sendTest', $invoiceTemplate->ulid);

    // If sendTest() had thrown (e.g. the queue-serialization bug this guards
    // against), it would have been swallowed into a flashed session error and
    // neither mailable would ever have reached Mail::sendNow().
    Mail::assertSent(SubscriptionReminderMail::class);
    Mail::assertSent(InvoiceMail::class);
});

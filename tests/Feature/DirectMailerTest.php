<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\MailTemplates\DirectMailer;
use App\Mail\GenericClientMail;
use App\Models\Client;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('mail-mailer.index'))->assertRedirect(route('login'));
});

test('non super admins cannot access the direct mailer', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->get(route('mail-mailer.index'))
        ->assertForbidden();
});

test('super admins can view the direct mailer', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get(route('mail-mailer.index'))
        ->assertOk();
});

test('sending queues an email for each selected client and resets the form', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $client = Client::factory()->create();

    Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('selectedClients', [$client->ulid])
        ->set('subject', 'Hello there')
        ->set('body', 'This is the message body.')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('notify', type: 'success');

    Mail::assertQueued(GenericClientMail::class, fn ($mail) => $mail->client->is($client));
});

test('sending requires at least one recipient, a subject, and a body', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('selectedClients', [])
        ->set('subject', '')
        ->set('body', '')
        ->call('send')
        ->assertHasErrors(['selectedClients', 'subject', 'body']);
});

test('select all selects every matching client across pages, not just the current page', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $clients = Client::factory()->count(8)->create();

    $component = Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('selectAll', true);

    expect(collect($component->get('selectedClients'))->sort()->values()->all())
        ->toBe($clients->pluck('ulid')->sort()->values()->all());
});

test('sending is rate limited after 3 attempts within a minute', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $client = Client::factory()->create();

    $component = Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('selectedClients', [$client->ulid])
        ->set('subject', 'Hello there')
        ->set('body', 'This is the message body.');

    for ($i = 0; $i < 4; $i++) {
        $component
            ->set('selectedClients', [$client->ulid])
            ->set('subject', 'Hello there')
            ->set('body', 'This is the message body.')
            ->call('send');
    }

    $component->assertDispatched('notify', type: 'error');

    Mail::assertQueuedCount(3);
});

test('typing a subject or body marks the draft as manually edited', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->assertSet('hasManualEdits', false)
        ->set('subject', 'My own subject')
        ->assertSet('hasManualEdits', true);
});

test('applying a template does not itself count as a manual edit', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $template = MailTemplate::factory()->create();

    Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('selectedTemplate', $template->slug)
        ->assertSet('subject', $template->subject)
        ->assertSet('hasManualEdits', false);
});

test('preview renders placeholders for the first selected client', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $client = Client::factory()->create(['name' => 'Acme Co']);

    $component = Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('selectedClients', [$client->ulid])
        ->set('subject', 'Hello {client_name}')
        ->set('body', 'Dear {client_name}, thanks for your business.')
        ->call('openPreview')
        ->assertSet('showPreview', true);

    expect($component->get('previewRendered'))->toBe([
        'subject' => 'Hello Acme Co',
        'body' => 'Dear Acme Co, thanks for your business.',
    ]);
});

test('preview falls back to the raw draft when no recipient is selected', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $component = Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('subject', 'Hello {client_name}')
        ->set('body', 'Dear {client_name}.');

    expect($component->get('previewRendered'))->toBe([
        'subject' => 'Hello {client_name}',
        'body' => 'Dear {client_name}.',
    ]);
});

test('searching resets pagination back to the first page', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    Client::factory()->count(8)->create();

    $component = Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->call('gotoPage', 2)
        ->set('search', 'someone');

    expect($component->instance()->getPage())->toBe(1);
});

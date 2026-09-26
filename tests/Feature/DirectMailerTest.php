<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\MailTemplates\DirectMailer;
use App\Mail\GenericClientMail;
use App\Models\Client;
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
        ->assertHasNoErrors();

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

test('searching resets pagination back to the first page', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    Client::factory()->count(8)->create();

    $component = Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->call('gotoPage', 2)
        ->set('search', 'someone');

    expect($component->instance()->getPage())->toBe(1);
});

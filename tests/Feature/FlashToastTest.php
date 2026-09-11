<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Success/error flash messages render as a single, auto-dismissing toast
 * from the shared app layout — every page used to carry its own copy of
 * this markup (differently styled, several duplicating the layout's own
 * inline banner so the message showed twice), consolidated into one
 * reusable <x-ui.toast> component.
 */
test('a success flash renders as a toast and an error flash does not appear when absent', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    session()->flash('success', 'Client deleted successfully.');

    $html = view('components.layouts.app', ['slot' => 'content'])->render();

    expect($html)->toContain('Client deleted successfully.');
    expect($html)->toContain('fixed bottom-4 right-4');
    expect($html)->toContain('alert-success');
    expect($html)->not->toContain('alert-error');
});

test('an error flash renders as a toast', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    session()->flash('error', 'Something went wrong.');

    $html = view('components.layouts.app', ['slot' => 'content'])->render();

    expect($html)->toContain('Something went wrong.');
    expect($html)->toContain('alert-error');
});

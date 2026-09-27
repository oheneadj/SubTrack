<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Users\UserShow;
use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Livewire;

function createActivityLogsFor(User $targetUser, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        ActivityLog::create([
            'action' => 'user.updated',
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'description' => "Activity {$i}",
        ]);
    }
}

test('account history is paginated at 10 per page', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $targetUser = User::factory()->create();

    createActivityLogsFor($targetUser, 15);

    $component = Livewire::actingAs($admin)->test(UserShow::class, ['user' => $targetUser]);

    expect($component->get('recentActivity')->count())->toBe(10);
    expect($component->get('recentActivity')->total())->toBe(15);
    expect($component->instance()->getPage())->toBe(1);
});

test('a second page of account history can be navigated to', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $targetUser = User::factory()->create();

    createActivityLogsFor($targetUser, 15);

    $component = Livewire::actingAs($admin)
        ->test(UserShow::class, ['user' => $targetUser])
        ->call('gotoPage', 2);

    expect($component->get('recentActivity')->count())->toBe(5);
});

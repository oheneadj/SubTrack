<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UserShow extends Component
{
    use WithPagination;

    public User $user;

    public bool $passwordResetDone = false;

    public string $newPassword = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function toggleActive(ActivityLogService $activityLog): void
    {
        if ($this->user->id === auth()->id()) {
            session()->flash('error', 'You cannot disable your own account.');

            return;
        }

        $this->user->update(['is_active' => ! $this->user->is_active]);
        $action = $this->user->is_active ? 'enabled' : 'disabled';

        $activityLog->log("user.{$action}", "Account {$action} for {$this->user->name}", $this->user);

        session()->flash('success', "User account has been {$action}.");
    }

    public function initiatePasswordReset(): void
    {
        $this->passwordResetDone = false;
        $this->newPassword = '';
        // Dispatch event to open the modal via project-standard Alpine event
        $this->dispatch('open-modal', id: 'reset-password-modal');
    }

    public function confirmPasswordReset(ActivityLogService $activityLog): void
    {
        $this->newPassword = Str::random(12);
        $this->user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $activityLog->log('user.password_reset', "Password reset for {$this->user->name}", $this->user);

        $this->passwordResetDone = true;
        session()->flash('success', 'Password has been reset successfully.');
    }

    /**
     * Activity where this user is either the actor or the affected subject,
     * paginated so a long-lived account's history doesn't get silently
     * truncated to whatever the first page happened to hold.
     */
    #[Computed]
    public function recentActivity()
    {
        return ActivityLog::query()
            ->where('user_id', $this->user->id)
            ->orWhere(function ($query): void {
                $query->where('subject_type', User::class)
                    ->where('subject_id', $this->user->id);
            })
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.users.user-show');
    }
}

<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserShow extends Component
{
    public User $user;
    public bool $passwordResetDone = false;
    public string $newPassword = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function toggleActive(): void
    {
        if ($this->user->id === auth()->id()) {
            session()->flash('error', 'You cannot disable your own account.');
            return;
        }

        $this->user->update(['is_active' => ! $this->user->is_active]);
        $action = $this->user->is_active ? 'enabled' : 'disabled';
        session()->flash('success', "User account has been {$action}.");
    }

    public function initiatePasswordReset(): void
    {
        $this->passwordResetDone = false;
        $this->newPassword = '';
        // Dispatch event to open the modal via project-standard Alpine event
        $this->dispatch('open-modal', id: 'reset-password-modal');
    }

    public function confirmPasswordReset(): void
    {
        $this->newPassword = Str::random(12);
        $this->user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->passwordResetDone = true;
        session()->flash('success', "Password has been reset successfully.");
    }

    public function render()
    {
        return view('livewire.users.user-show');
    }
}

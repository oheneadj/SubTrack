<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserShow extends Component
{
    public User $user;
    public bool $showResetModal = false;
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
        $this->newPassword = Str::random(12);
        $this->showResetModal = true;
    }

    public function confirmPasswordReset(): void
    {
        $this->user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->showResetModal = false;
        session()->flash('success', "Password has been reset successfully. New password: {$this->newPassword}");
        // In a real app, we'd email this, but for now we'll show it in the flash message.
    }

    public function render()
    {
        return view('livewire.users.user-show');
    }
}

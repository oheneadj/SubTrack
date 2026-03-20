<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Mail\UserInviteMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UserIndex extends Component
{
    use WithPagination;

    // Search
    public string $search = '';

    // Invite Modal
    public bool $showInviteModal = false;

    #[Validate('required|string|max:255')]
    public string $inviteName = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $inviteEmail = '';

    public string $inviteRole = 'user';

    // Toggle Modal
    public bool $showToggleModal = false;
    public ?int $toggleUserId = null;
    public bool $toggleUserIsActive = false;
    public string $confirmPassword = '';

    // ─── Computed ─────────────────────────────────────

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    // ─── Actions ──────────────────────────────────────

    public function openInvite(): void
    {
        $this->reset('inviteName', 'inviteEmail', 'inviteRole');
        $this->resetValidation();
        $this->showInviteModal = true;
    }

    public function sendInvite(): void
    {
        $this->validate();

        $plainPassword = Str::random(12);

        $user = User::create([
            'name'     => $this->inviteName,
            'email'    => $this->inviteEmail,
            'password' => $plainPassword,
            'role'     => $this->inviteRole,
            'is_active' => true,
        ]);

        Mail::to($user->email)->send(new UserInviteMail(
            userName: $user->name,
            userEmail: $user->email,
            plainPassword: $plainPassword,
            loginUrl: route('login'),
        ));

        $this->showInviteModal = false;
        $this->reset('inviteName', 'inviteEmail', 'inviteRole');

        session()->flash('success', "Invitation sent to {$user->email} successfully.");
    }

    public function openToggleModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->toggleUserId = $user->id;
        $this->toggleUserIsActive = $user->is_active;
        $this->confirmPassword = '';
        $this->resetErrorBag('confirmPassword');
        $this->showToggleModal = true;
    }

    public function confirmToggleActive(): void
    {
        $this->validate(['confirmPassword' => 'required|string']);

        if (! Hash::check($this->confirmPassword, auth()->user()->password)) {
            $this->addError('confirmPassword', 'Incorrect password.');
            return;
        }

        $user = User::findOrFail($this->toggleUserId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot disable your own account.');
            $this->showToggleModal = false;
            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        $action = $user->is_active ? 'enabled' : 'disabled';
        session()->flash('success', "{$user->name} has been {$action}.");

        $this->showToggleModal = false;
        $this->reset('confirmPassword', 'toggleUserId', 'toggleUserIsActive');
    }

    public function resendInvite(int $userId): void
    {
        $user = User::findOrFail($userId);

        $plainPassword = Str::random(12);
        $user->update(['password' => $plainPassword]);

        Mail::to($user->email)->send(new UserInviteMail(
            userName: $user->name,
            userEmail: $user->email,
            plainPassword: $plainPassword,
            loginUrl: route('login'),
        ));

        session()->flash('success', "New credentials sent to {$user->email}.");
    }

    public function confirmDelete(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $name = $user->name;
        $user->delete();

        session()->flash('success', "{$name} has been removed.");
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.users.user-index');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Livewire\Concerns\Notifies;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class ForcePasswordChange extends Component
{
    use Notifies;

    public string $password = '';

    public string $password_confirmation = '';

    public function save()
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        try {
            // Force a fresh instance to ensure no stale data
            $user = $user->fresh();

            $user->password = Hash::make($this->password);
            $user->requires_password_change = false;
            $user->invitation_accepted_at = now();
            $user->save();

            // Re-login to refresh the session guard state
            Auth::login($user);

            // Notify all super admins that this user accepted the invite
            $superAdmins = User::where('role', UserRole::SuperAdmin)->where('id', '!=', $user->id)->get();
            Notification::send($superAdmins, new SystemNotification(
                title: 'Invitation Accepted',
                message: "{$user->name} ({$user->email}) has accepted their invitation and joined the platform.",
                actionUrl: route('users.index'),
            ));

            Log::info('Password changed for user: '.$user->email.'. Flag is now: '.$user->requires_password_change);

            session()->flash('success', 'Your password has been secured successfully. Welcome to your dashboard!');

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            Log::error('Password change failed: '.$e->getMessage());
            $this->notifyError('There was a problem updating your password. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}

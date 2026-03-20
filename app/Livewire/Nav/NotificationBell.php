<?php

namespace App\Livewire\Nav;

use Livewire\Component;
use Illuminate\View\View;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    protected $listeners = [
        'echo:notifications,NotificationSent' => 'refreshCount',
        'notificationRead' => 'refreshCount',
    ];

    public function mount(): void
    {
        $this->refreshCount();
    }

    public function refreshCount(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user) {
            $this->unreadCount = $user->unreadNotifications()->count();
        }
    }

    public function render(): View
    {
        return view('livewire.nav.notification-bell');
    }
}

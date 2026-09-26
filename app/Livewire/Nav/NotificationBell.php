<?php

declare(strict_types=1);

namespace App\Livewire\Nav;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

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
        /** @var User|null $user */
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

<?php

namespace App\Livewire\Nav;

use Livewire\Component;
use Illuminate\View\View;
use Illuminate\Support\Collection;

class NotificationList extends Component
{
    public function markAsRead(string $id): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $notification = $user->unreadNotifications->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
            
            app(\App\Services\ActivityLogService::class)->log(
                action: 'notification.read',
                description: 'Marked notification as read: ' . ($notification->data['title'] ?? 'Unknown'),
                properties: ['notification_id' => $id]
            );
        }
        
        $this->dispatch('notificationRead');
    }

    public function markAllAsRead(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $count = $user->unreadNotifications()->count();
        
        if ($count > 0) {
            $user->unreadNotifications->markAsRead();
            
            app(\App\Services\ActivityLogService::class)->log(
                action: 'notification.read_all',
                description: "Marked $count notifications as read",
                properties: ['count' => $count]
            );
        }
        
        $this->dispatch('notificationRead');
    }

    public function render(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return view('livewire.nav.notification-list', [
            'notifications' => $user->notifications()->latest()->take(20)->get(),
        ]);
    }
}

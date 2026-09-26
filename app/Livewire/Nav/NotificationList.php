<?php

declare(strict_types=1);

namespace App\Livewire\Nav;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\View\View;
use Livewire\Component;

class NotificationList extends Component
{
    public function markAsRead(string $id): void
    {
        /** @var User $user */
        $user = auth()->user();
        $notification = $user->unreadNotifications->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();

            app(ActivityLogService::class)->log(
                action: 'notification.read',
                description: 'Marked notification as read: '.($notification->data['title'] ?? 'Unknown'),
                properties: ['notification_id' => $id]
            );
        }

        $this->dispatch('notificationRead');
    }

    public function markAllAsRead(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $count = $user->unreadNotifications()->count();

        if ($count > 0) {
            $user->unreadNotifications->markAsRead();

            app(ActivityLogService::class)->log(
                action: 'notification.read_all',
                description: "Marked $count notifications as read",
                properties: ['count' => $count]
            );
        }

        $this->dispatch('notificationRead');
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('livewire.nav.notification-list', [
            'notifications' => $user->notifications()->latest()->take(20)->get(),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\Notification;
use Livewire\Component;

class NavbarNotificationBell extends Component
{
    public $unreadCount = 0;
    public $notifications = [];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (auth()->check()) {
            $this->unreadCount = Notification::where('user_id', auth()->id())
                ->where('is_read', false)
                ->count();

            $this->notifications = Notification::where('user_id', auth()->id())
                ->latest()
                ->take(5)
                ->get();
        }
    }

    public function markAsRead($id)
    {
        $notif = Notification::where('user_id', auth()->id())->find($id);
        if ($notif) {
            $notif->update(['is_read' => true]);
            $this->loadNotifications();
        }
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())->update(['is_read' => true]);
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.navbar-notification-bell');
    }
}

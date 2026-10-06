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
                ->get()
                ->toArray();
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

    /**
     * Mark the viewer's own notification read and, when it carries a link into this app, go there.
     */
    public function open(int $id): void
    {
        $notif = Notification::where('user_id', auth()->id())->find($id);
        if (!$notif) {
            return;
        }

        $notif->update(['is_read' => true]);
        $this->loadNotifications();

        if ($this->isInternalLink($notif->link)) {
            $this->redirect($notif->link, navigate: true);
        }
    }

    /**
     * Only same-site paths and URLs are followed; anything else (other hosts, protocol-relative
     * "//host" links, javascript:) is ignored so a stored link can never send users off-site.
     */
    private function isInternalLink(?string $link): bool
    {
        if ($link === null || $link === '') {
            return false;
        }

        if (str_starts_with($link, '/')) {
            return !str_starts_with($link, '//') && !str_starts_with($link, '/\\');
        }

        $scheme = strtolower((string) parse_url($link, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($link, PHP_URL_HOST));
        $allowedHosts = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower(request()->getHost()),
        ]);

        return in_array($scheme, ['http', 'https'], true) && in_array($host, $allowedHosts, true);
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

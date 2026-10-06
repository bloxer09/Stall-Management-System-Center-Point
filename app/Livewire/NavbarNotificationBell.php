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

        if ($path = $this->internalPath($notif->link)) {
            $this->redirect($path, navigate: true);
        }
    }

    /**
     * The same-site path (with query and fragment) a stored link points to, or null when it
     * must not be followed. Redirecting by path keeps the browser on whatever origin it is on
     * (localhost vs 127.0.0.1), and anything ambiguous — other hosts, "//host", javascript:,
     * userinfo, backslashes, control characters — is refused rather than guessed at.
     */
    private function internalPath(?string $link): ?string
    {
        if ($link === null || $link === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $link)) {
            return null;
        }

        if (str_starts_with($link, '/')) {
            return str_starts_with($link, '//') ? null : $link;
        }

        $parts = parse_url($link);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        $allowedHosts = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower(request()->getHost()),
        ]);
        if (!in_array($scheme, ['http', 'https'], true) || !in_array($host, $allowedHosts, true)) {
            return null;
        }

        return ($parts['path'] ?? '/')
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
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

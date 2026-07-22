<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Notification;
use App\Config\App;

class NotificationController extends Controller
{
    private $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
    }

    public function index()
    {
        Auth::requireLogin();
        $user = Auth::user();

        $notifications = $this->notificationModel->getForUser($user['id'], 50);

        return $this->view('pages/notifications', [
            'title' => App::pageTitle('Notification Center'),
            'notifications' => $notifications
        ]);
    }

    public function getUnreadCount()
    {
        if (!Auth::isLoggedIn()) {
            return $this->jsonResponse(['unread_count' => 0]);
        }

        $count = $this->notificationModel->getUnreadCount(Auth::user()['id']);
        return $this->jsonResponse(['unread_count' => $count]);
    }

    public function markRead($id)
    {
        Auth::requireLogin();
        $userId = Auth::user()['id'];
        $notificationId = (int) $id;

        $this->notificationModel->markAsRead($notificationId, $userId);

        if ($this->isAjaxRequest()) {
            return $this->jsonResponse(['status' => 'success']);
        }

        Session::setFlash('success', 'Notification marked as read.');
        $this->redirect('/notifications');
    }

    public function markAllRead()
    {
        Auth::requireLogin();
        $userId = Auth::user()['id'];

        $this->notificationModel->markAllAsRead($userId);

        if ($this->isAjaxRequest()) {
            return $this->jsonResponse(['status' => 'success']);
        }

        Session::setFlash('success', 'All notifications marked as read.');
        $this->redirect('/notifications');
    }
}

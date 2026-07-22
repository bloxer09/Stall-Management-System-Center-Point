<?php

namespace App\Controllers;

use App\Config\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Services\FileUploadService;

class ProfileController extends Controller
{
    private $userModel;
    private $uploadService;

    public function __construct()
    {
        Auth::requireLogin();
        $this->userModel = new User();
        $this->uploadService = new FileUploadService();
    }

    public function index()
    {
        $isStaffOrAdmin = Auth::isStaffOrAdmin();
        if ($isStaffOrAdmin) {
            Auth::requireStaffOrAdmin();
        } else {
            Auth::requireUser();
        }

        $user = $this->userModel->findById(Auth::user()['id']);

        return $this->view($isStaffOrAdmin ? 'pages/admin/profile' : 'pages/user/profile', [
            'title' => 'My Profile',
            'profile' => $user,
        ]);
    }

    public function update()
    {
        $profileRoute = Auth::isStaffOrAdmin() ? '/admin/dashboard' : '/user/dashboard';
        if (Auth::isStaffOrAdmin()) {
            Auth::requireStaffOrAdmin();
        } else {
            Auth::requireUser();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($profileRoute);
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect($profileRoute);
            return;
        }

        $userId = Auth::user()['id'];
        $current = $this->userModel->findById($userId);
        if (!$current) {
            Session::setFlash('error', 'User not found.');
            $this->redirect('/?login=1');
            return;
        }

        $data = [];

        try {
            if (!empty($_POST['remove_profile_picture'])) {
                if (!empty($current['profile_picture'])) {
                    $this->deleteLocalFile($current['profile_picture']);
                }
                $data['profile_picture'] = null;
            }

            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload = $this->uploadService->store($_FILES['profile_picture'], 'avatars', [
                    'visibility' => 'public',
                    'max_size' => 2 * 1024 * 1024,
                    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                ]);

                if (!empty($current['profile_picture'])) {
                    $this->deleteLocalFile($current['profile_picture']);
                }

                $data['profile_picture'] = $upload['path'];
            }
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Profile picture upload failed: ' . $e->getMessage());
            $this->redirect($profileRoute);
            return;
        }

        if ($data === []) {
            Session::setFlash('info', 'No profile changes were submitted.');
            $this->redirect($profileRoute);
            return;
        }

        if (!$this->userModel->updateProfile($userId, $data)) {
            Session::setFlash('error', 'Failed to update profile.');
            $this->redirect($profileRoute);
            return;
        }

        $this->refreshSessionUser($userId);
        Session::setFlash('success', 'Profile updated successfully.');
        $this->redirect($profileRoute);
    }

    private function refreshSessionUser($userId)
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return;
        }

        unset($user['password']);
        Session::set('user', $user);
        Session::set('user_role', $user['role']);
    }

    private function deleteLocalFile($relativePath)
    {
        $clean = ltrim((string)$relativePath, '/\\');
        $absolute = App::projectRoot($clean);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}

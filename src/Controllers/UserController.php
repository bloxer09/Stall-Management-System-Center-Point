<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Core\Auth;
use App\Core\Session;
use App\Services\RegistrationVerification;

class UserController extends Controller {
    private $userModel;

    public function __construct() {
        Auth::requireAdmin();
        $this->userModel = new User();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
        $sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

        $limit = 10;
        $users = $this->userModel->getPaginated($page, $limit, $search, $sortBy, $sortOrder);

        return $this->view('pages/admin/users/index', [
            'title' => 'Manage Users',
            'users' => $users['data'],
            'total' => $users['total'],
            'page' => $users['page'],
            'pages' => $users['pages'],
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]);
    }

    public function create() {
        $formUser = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->ensurePhoneVerificationSchema('/admin/users/create')) {
                return;
            }

            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/users');
                return;
            }

            $normalizedPhone = RegistrationVerification::normalizePhone((string) ($_POST['phone'] ?? ''));
            $data = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $normalizedPhone,
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? 'user',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'phone_verified_at' => date('Y-m-d H:i:s'),
            ];
            $formUser = array_merge($data, ['phone' => $_POST['phone'] ?? '']);

            // Basic validation
            if (empty($data['name']) || empty($data['email']) || empty($data['phone']) || empty($data['password'])) {
                Session::setFlash('error', 'All fields are required.');
            } else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                Session::setFlash('error', 'Enter a valid email address.');
            } else if ($normalizedPhone === null) {
                Session::setFlash('error', 'Enter a valid phone number.');
            } else if (strlen($data['password']) < 8) {
                Session::setFlash('error', 'Password must be at least 8 characters long.');
            } else if ($this->userModel->findByEmail($data['email'])) {
                Session::setFlash('error', 'Email already exists.');
            } else if ($this->userModel->findByPhone($data['phone'])) {
                Session::setFlash('error', 'Phone number already exists.');
            } else if ($this->userModel->create($data)) {
                (new AuditLog())->logAction(Auth::user()['id'], 'user_created_by_admin', 'User', null, [
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'role' => $data['role'],
                ]);
                Session::setFlash('success', 'User created successfully.');
            } else {
                Session::setFlash('error', 'Failed to create user.');
            }
            $this->redirect('/admin/users');
            return;
        }
        
        return $this->view('pages/admin/users/form', [
            'title' => 'Create User',
            'user' => $formUser
        ]);
    }

    public function edit($id) {
        $user = $this->userModel->findById($id);
        if (!$user) {
            Session::setFlash('error', 'User not found.');
            $this->redirect('/admin/users');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->ensurePhoneVerificationSchema("/admin/users/edit/$id")) {
                return;
            }

            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/users');
                return;
            }

            $normalizedPhone = RegistrationVerification::normalizePhone((string) ($_POST['phone'] ?? ''));
            $data = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $normalizedPhone,
                'password' => $_POST['password'] ?? '', // optional on edit
                'role' => $_POST['role'] ?? 'user',
                'email_verified_at' => ($user['email'] ?? '') !== ($_POST['email'] ?? '') ? date('Y-m-d H:i:s') : ($user['email_verified_at'] ?? null),
                'phone_verified_at' => ($user['phone'] ?? '') !== $normalizedPhone ? date('Y-m-d H:i:s') : ($user['phone_verified_at'] ?? null),
            ];
            $user = array_merge($user, $data, ['phone' => $_POST['phone'] ?? '']);

            // Check email uniqueness if changed
            $existing = $this->userModel->findByEmail($data['email']);
            if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
                Session::setFlash('error', 'Name, email, and phone number are required.');
            } else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                Session::setFlash('error', 'Enter a valid email address.');
            } else if ($normalizedPhone === null) {
                Session::setFlash('error', 'Enter a valid phone number.');
            } else if ($data['password'] !== '' && strlen($data['password']) < 8) {
                Session::setFlash('error', 'Password must be at least 8 characters long.');
            } else if ($existing && $existing['id'] != $id) {
                Session::setFlash('error', 'Email already exists for another user.');
            } else if ($this->userModel->phoneExistsForOtherUser($data['phone'], $id)) {
                Session::setFlash('error', 'Phone number already exists for another user.');
            } else if ($this->userModel->update($id, $data)) {
                (new AuditLog())->logAction(Auth::user()['id'], 'user_updated_by_admin', 'User', $id, [
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'role' => $data['role'],
                ]);
                Session::setFlash('success', 'User updated successfully.');
            } else {
                Session::setFlash('error', 'Failed to update user.');
            }
            $this->redirect('/admin/users');
            return;
        }

        return $this->view('pages/admin/users/form', [
            'title' => 'Edit User - ' . htmlspecialchars($user['name']),
            'user' => $user
        ]);
    }

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/users');
                return;
            }

            // Prevent deleting self
            if ($id == Auth::user()['id']) {
                Session::setFlash('error', 'You cannot delete yourself.');
            } else if ($this->userModel->getUsersWithPaymentHistory([(int)$id]) !== []) {
                Session::setFlash('error', 'This user cannot be deleted because they have payment records.');
            } else if ($this->userModel->delete($id)) {
                (new AuditLog())->logAction(Auth::user()['id'], 'user_deleted_by_admin', 'User', $id);
                Session::setFlash('success', 'User deleted successfully.');
            } else {
                Session::setFlash('error', 'Failed to delete user.');
            }
        }
        $this->redirect('/admin/users');
    }

    public function bulkAction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/users');
                return;
            }

            $action = $_POST['action'] ?? '';
            $ids = isset($_POST['ids']) ? explode(',', trim($_POST['ids'])) : [];
            
            if (empty($ids)) {
                Session::setFlash('error', 'No items selected.');
                $this->redirect('/admin/users');
            }

            // Remove self from bulk delete
            $selfId = Auth::user()['id'];
            $ids = array_diff($ids, [$selfId]);

            if ($action === 'delete') {
                if (!empty($ids)) {
                    $normalizedIds = array_values(array_unique(array_map('intval', $ids)));
                    $blockedUsers = $this->userModel->getUsersWithPaymentHistory($normalizedIds);
                    $blockedIds = array_map('intval', array_column($blockedUsers, 'id'));
                    $deletableIds = array_values(array_diff($normalizedIds, $blockedIds));

                    $deleted = $deletableIds === [] ? false : $this->userModel->bulkDelete($deletableIds);
                    if ($deleted) {
                        (new AuditLog())->logAction(Auth::user()['id'], 'user_bulk_deleted_by_admin', 'User', null, ['ids' => $deletableIds]);
                    }

                    if ($deleted && $blockedUsers === []) {
                        Session::setFlash('success', 'Selected users deleted.');
                    } else if ($deleted && $blockedUsers !== []) {
                        $names = implode(', ', array_map(static function ($user) {
                            return $user['name'];
                        }, array_slice($blockedUsers, 0, 3)));
                        $suffix = count($blockedUsers) > 3 ? ' and others' : '';
                        Session::setFlash('warning', 'Some users were skipped because they have payment records: ' . $names . $suffix . '.');
                    } else if ($blockedUsers !== []) {
                        Session::setFlash('error', 'Selected users cannot be deleted because they have payment records.');
                    } else {
                        Session::setFlash('error', 'Failed to delete selected users.');
                    }
                } else {
                    Session::setFlash('error', "Could not perform action. You cannot delete yourself.");
                }
            } else {
                Session::setFlash('error', 'Invalid action.');
            }
        }
        $this->redirect('/admin/users');
    }

    public function exportCsv() {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
        $sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

        $users = $this->userModel->getPaginated(1, 100000, $search, $sortBy, $sortOrder)['data'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_export_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Email Verified At', 'Phone Verified At', 'Role', 'Created At']);

        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['phone'],
                $user['email_verified_at'],
                $user['phone_verified_at'],
                $user['role'],
                $user['created_at']
            ]);
        }
        fclose($output);
        exit();
    }

    private function ensurePhoneVerificationSchema(string $redirectPath): bool {
        if ($this->userModel->supportsPhoneVerificationSchema()) {
            return true;
        }

        Session::setFlash('error', 'Phone-based user management requires the latest database update. Run php update_database.php or update_database.cmd first.');
        $this->redirect($redirectPath);
        return false;
    }
}

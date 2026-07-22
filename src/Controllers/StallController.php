<?php

namespace App\Controllers;

use App\Config\App;
use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Core\Auth;
use App\Core\Session;
use App\Services\FileUploadService;

class StallController extends Controller {
    private $stallModel;
    private $locationModel;
    private $uploadService;

    public function __construct() {
        Auth::requireStaffOrAdmin();
        $this->stallModel = new Stall();
        $this->locationModel = new StallLocation();
        $this->uploadService = new FileUploadService();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 's.stall_number';
        $sortOrder = isset($_GET['order']) ? (string)$_GET['order'] : 'ASC';

        $limit = 10;
        $stalls = $this->stallModel->getPaginated($page, $limit, $search, $sortBy, $sortOrder);

        $locations = $this->locationModel->getAll();

        return $this->view('pages/admin/stalls/index', [
            'title' => 'Manage Stalls',
            'stalls' => $stalls['data'],
            'total' => $stalls['total'],
            'page' => $stalls['page'],
            'pages' => $stalls['pages'],
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'locations' => $locations,
        ]);
    }

    public function create() {
        $locations = $this->locationModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/stalls');
                return;
            }

            $data = [
                'stall_number' => trim((string)($_POST['stall_number'] ?? '')),
                'price' => (float)($_POST['price'] ?? 0),
                'status' => (string)($_POST['status'] ?? 'available'),
                'description' => trim((string)($_POST['description'] ?? '')),
                'location_id' => $_POST['location_id'] ?? null,
                'grid_row' => isset($_POST['grid_row']) && $_POST['grid_row'] !== '' ? (int)$_POST['grid_row'] : null,
                'grid_col' => isset($_POST['grid_col']) && $_POST['grid_col'] !== '' ? (int)$_POST['grid_col'] : null,
            ];

            $errors = $this->validateStallData($data);

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $upload = $this->uploadService->store($_FILES['image'], 'stalls', [
                        'visibility' => 'public',
                        'max_size' => 4 * 1024 * 1024,
                        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
                    ]);
                    $data['image'] = $upload['path'];
                } catch (\Throwable $e) {
                    $errors[] = 'Image upload failed: ' . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                Session::setFlash('error', implode(' ', $errors));
            } else {
                if ($this->stallModel->create($data)) {
                    (new AuditLog())->logAction(Auth::user()['id'], 'stall_created', 'Stall', null, $data);
                    Session::setFlash('success', 'Stall created successfully.');
                }
            }
            $this->redirect('/admin/stalls');
            return;
        }

        return $this->view('pages/admin/stalls/form', [
            'title' => 'Create Stall',
            'stall' => null,
            'locations' => $locations,
        ]);
    }

    public function edit($id) {
        $stall = $this->stallModel->findById($id);
        if (!$stall) {
            Session::setFlash('error', 'Stall not found.');
            $this->redirect('/admin/stalls');
            return;
        }

        $locations = $this->locationModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/stalls');
                return;
            }

            $data = [
                'stall_number' => trim((string)($_POST['stall_number'] ?? '')),
                'price' => (float)($_POST['price'] ?? 0),
                'status' => (string)($_POST['status'] ?? 'available'),
                'description' => trim((string)($_POST['description'] ?? '')),
                'location_id' => $_POST['location_id'] ?? null,
                'grid_row' => isset($_POST['grid_row']) && $_POST['grid_row'] !== '' ? (int)$_POST['grid_row'] : null,
                'grid_col' => isset($_POST['grid_col']) && $_POST['grid_col'] !== '' ? (int)$_POST['grid_col'] : null,
            ];

            $errors = $this->validateStallData($data);

            if (!empty($_POST['remove_image'])) {
                $data['image'] = null;
            }

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $upload = $this->uploadService->store($_FILES['image'], 'stalls', [
                        'visibility' => 'public',
                        'max_size' => 4 * 1024 * 1024,
                        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
                    ]);
                    $data['image'] = $upload['path'];
                } catch (\Throwable $e) {
                    $errors[] = 'Image upload failed: ' . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                Session::setFlash('error', implode(' ', $errors));
            } else {
                if ($this->stallModel->update($id, $data)) {
                    if (array_key_exists('image', $data) && !empty($stall['image']) && $stall['image'] !== $data['image']) {
                        $this->deleteStoredFile($stall['image']);
                    }
                    (new AuditLog())->logAction(Auth::user()['id'], 'stall_updated', 'Stall', $id, $data);
                    Session::setFlash('success', 'Stall updated successfully.');
                } else {
                    Session::setFlash('error', 'Failed to update stall.');
                }
            }
            $this->redirect('/admin/stalls');
            return;
        }

        return $this->view('pages/admin/stalls/form', [
            'title' => 'Edit Stall - ' . htmlspecialchars($stall['stall_number']),
            'stall' => $stall,
            'locations' => $locations,
        ]);
    }

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/stalls');
                return;
            }

            $blocked = $this->stallModel->getStallsWithPaymentHistory([(int)$id]);
            if ($blocked !== []) {
                Session::setFlash('error', 'This stall cannot be deleted because it has payment records.');
            } else if ($this->stallModel->delete($id)) {
                (new AuditLog())->logAction(Auth::user()['id'], 'stall_deleted', 'Stall', $id);
                Session::setFlash('success', 'Stall deleted successfully.');
            } else {
                Session::setFlash('error', 'Failed to delete stall.');
            }
        }
        $this->redirect('/admin/stalls');
    }

    public function bulkAction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/stalls');
                return;
            }

            $action = $_POST['action'] ?? '';
            $ids = isset($_POST['ids']) ? explode(',', trim((string)$_POST['ids'])) : [];
            $ids = array_values(array_filter(array_map('intval', $ids)));

            if (empty($ids)) {
                Session::setFlash('error', 'No items selected.');
                $this->redirect('/admin/stalls');
                return;
            }

            if ($action === 'delete') {
                $blockedStalls = $this->stallModel->getStallsWithPaymentHistory($ids);
                $blockedIds = array_map('intval', array_column($blockedStalls, 'id'));
                $deletableIds = array_values(array_diff($ids, $blockedIds));

                $deleted = $deletableIds === [] ? false : $this->stallModel->bulkDelete($deletableIds);
                if ($deleted) {
                    (new AuditLog())->logAction(Auth::user()['id'], 'stall_bulk_deleted', 'Stall', null, ['ids' => $deletableIds]);
                }

                if ($deleted && $blockedStalls === []) {
                    Session::setFlash('success', 'Selected stalls deleted.');
                } else if ($deleted && $blockedStalls !== []) {
                    $names = implode(', ', array_map(static function ($stall) {
                        return $stall['stall_number'];
                    }, array_slice($blockedStalls, 0, 3)));
                    $suffix = count($blockedStalls) > 3 ? ' and others' : '';
                    Session::setFlash('warning', 'Some stalls were skipped because they have payment records: ' . $names . $suffix . '.');
                } else if ($blockedStalls !== []) {
                    Session::setFlash('error', 'Selected stalls cannot be deleted because they have payment records.');
                } else {
                    Session::setFlash('error', 'Failed to delete selected stalls.');
                }
            } elseif (in_array($action, ['available', 'occupied', 'maintenance'], true)) {
                $this->stallModel->bulkUpdateStatus($ids, $action);
                (new AuditLog())->logAction(Auth::user()['id'], "stall_bulk_status_{$action}", 'Stall', null, ['ids' => $ids]);
                Session::setFlash('success', "Selected stalls updated to {$action}.");
            } else {
                Session::setFlash('error', 'Invalid action.');
            }
        }
        $this->redirect('/admin/stalls');
    }

    public function exportCsv() {
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 's.stall_number';
        $sortOrder = isset($_GET['order']) ? (string)$_GET['order'] : 'ASC';

        $stalls = $this->stallModel->getPaginated(1, 100000, $search, $sortBy, $sortOrder)['data'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=stalls_export_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Stall Number', 'Location', 'Status', 'Price', 'Description', 'Created At']);

        foreach ($stalls as $stall) {
            fputcsv($output, [
                $stall['id'],
                $stall['stall_number'],
                $stall['location_name'] ?? '',
                $stall['status'],
                $stall['price'],
                $stall['description'],
                $stall['created_at']
            ]);
        }
        fclose($output);
        exit();
    }

    private function validateStallData(array $data): array {
        $errors = [];

        if ($data['stall_number'] === '') {
            $errors[] = 'Stall number is required.';
        }

        if (!in_array($data['status'], ['available', 'occupied', 'maintenance'], true)) {
            $errors[] = 'Invalid stall status.';
        }

        if ($data['price'] <= 0) {
            $errors[] = 'Price must be greater than zero.';
        }

        if (!empty($data['location_id']) && !is_numeric($data['location_id'])) {
            $errors[] = 'Invalid location selected.';
        }

        return $errors;
    }

    private function deleteStoredFile($relativePath): void {
        $clean = ltrim((string)$relativePath, '/\\');
        if ($clean === '' || strpos($clean, '..') !== false) {
            return;
        }

        $absolute = App::projectRoot($clean);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}

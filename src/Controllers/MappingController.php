<?php

namespace App\Controllers;

use App\Config\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Mapping;
use App\Models\Stall;
use App\Models\User;
use App\Services\FileUploadService;

class MappingController extends Controller {
    private $mappingModel;
    private $stallModel;
    private $userModel;
    private $uploadService;

    public function __construct() {
        Auth::requireStaffOrAdmin();
        $this->mappingModel = new Mapping();
        $this->stallModel = new Stall();
        $this->userModel = new User();
        $this->uploadService = new FileUploadService();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 'm.created_at';
        $sortOrder = isset($_GET['order']) ? (string)$_GET['order'] : 'DESC';

        $limit = 10;
        $mappings = $this->mappingModel->getPaginated($page, $limit, $search, $sortBy, $sortOrder);

        $users = $this->userModel->getPaginated(1, 1000)['data'];
        $stalls = $this->stallModel->getPaginated(1, 2000, '', 's.stall_number', 'ASC')['data'];
        $availableStalls = array_values(array_filter($stalls, function($s) {
            return $s['status'] === 'available';
        }));

        return $this->view('pages/admin/mappings/index', [
            'title' => 'Stall Assignments',
            'mappings' => $mappings['data'],
            'total' => $mappings['total'],
            'page' => $mappings['page'],
            'pages' => $mappings['pages'],
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'users' => $users,
            'stalls' => $stalls,
            'availableStalls' => $availableStalls,
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/mappings');
                return;
            }

            $data = [
                'user_id' => (int)($_POST['user_id'] ?? 0),
                'stall_id' => (int)($_POST['stall_id'] ?? 0),
                'start_date' => (string)($_POST['start_date'] ?? ''),
                'end_date' => (string)($_POST['end_date'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'contract_document' => null,
            ];

            $errors = $this->validateMappingData($data);

            if (isset($_FILES['contract']) && $_FILES['contract']['error'] === UPLOAD_ERR_OK) {
                try {
                    $upload = $this->uploadService->store($_FILES['contract'], 'contracts', [
                        'visibility' => 'private',
                        'max_size' => 8 * 1024 * 1024,
                        'allowed_extensions' => ['pdf'],
                        'allowed_mimes' => ['application/pdf'],
                    ]);
                    $data['contract_document'] = $upload['path'];
                } catch (\Throwable $e) {
                    $errors[] = 'Contract upload failed: ' . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                Session::setFlash('error', implode(' ', $errors));
            } else {
                if ($this->mappingModel->create($data)) {
                    (new AuditLog())->logAction(Auth::user()['id'], 'mapping_created', 'Mapping', null, $data);
                    Session::setFlash('success', 'Stall assigned successfully.');
                } else {
                    Session::setFlash('error', 'Failed to assign stall. The stall might already have an active assignment.');
                }
            }
            $this->redirect('/admin/mappings');
            return;
        }

        $users = $this->userModel->getPaginated(1, 1000)['data'];
        $stalls = $this->stallModel->getPaginated(1, 2000, '', 's.stall_number', 'ASC')['data'];

        return $this->view('pages/admin/mappings/form', [
            'title' => 'Assign Stall to Tenant',
            'mapping' => null,
            'users' => $users,
            'stalls' => array_values(array_filter($stalls, function($s) {
                return $s['status'] === 'available';
            }))
        ]);
    }

    public function edit($id) {
        $mapping = $this->mappingModel->findById($id);
        if (!$mapping) {
            Session::setFlash('error', 'Mapping not found.');
            $this->redirect('/admin/mappings');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/mappings');
                return;
            }

            $oldContractPath = $mapping['contract_document'] ?? null;
            $contractPath = $oldContractPath;

            if (isset($_FILES['contract']) && $_FILES['contract']['error'] === UPLOAD_ERR_OK) {
                try {
                    $upload = $this->uploadService->store($_FILES['contract'], 'contracts', [
                        'visibility' => 'private',
                        'max_size' => 8 * 1024 * 1024,
                        'allowed_extensions' => ['pdf'],
                        'allowed_mimes' => ['application/pdf'],
                    ]);
                    $contractPath = $upload['path'];
                } catch (\Throwable $e) {
                    Session::setFlash('error', 'Contract upload failed: ' . $e->getMessage());
                    $this->redirect('/admin/mappings');
                    return;
                }
            }

            if (!empty($_POST['remove_contract'])) {
                $contractPath = null;
            }

            $data = [
                'user_id' => (int)($_POST['user_id'] ?? 0),
                'stall_id' => (int)($_POST['stall_id'] ?? 0),
                'start_date' => (string)($_POST['start_date'] ?? ''),
                'end_date' => (string)($_POST['end_date'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'contract_document' => $contractPath,
            ];

            $errors = $this->validateMappingData($data);
            if (!empty($errors)) {
                Session::setFlash('error', implode(' ', $errors));
            } else {
            if ($this->mappingModel->update($id, $data)) {
                    if (!empty($oldContractPath) && $oldContractPath !== $contractPath) {
                        $this->deleteStoredFile($oldContractPath);
                    }
                    (new AuditLog())->logAction(Auth::user()['id'], 'mapping_updated', 'Mapping', $id, $data);
                    Session::setFlash('success', 'Assignment updated successfully.');
                } else {
                    Session::setFlash('error', 'Failed to update mapping. The stall might already have an active assignment.');
                }
            }
            $this->redirect('/admin/mappings');
            return;
        }

        $users = $this->userModel->getPaginated(1, 1000)['data'];
        $stalls = $this->stallModel->getPaginated(1, 2000, '', 's.stall_number', 'ASC')['data'];

        return $this->view('pages/admin/mappings/form', [
            'title' => 'Edit Stall Assignment',
            'mapping' => $mapping,
            'users' => $users,
            'stalls' => $stalls
        ]);
    }

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/mappings');
                return;
            }

            if ($this->mappingModel->delete($id)) {
                (new AuditLog())->logAction(Auth::user()['id'], 'mapping_deleted', 'Mapping', $id);
                Session::setFlash('success', 'Assignment removed successfully.');
            } else {
                Session::setFlash('error', 'Failed to remove assignment.');
            }
        }

        $this->redirect('/admin/mappings');
    }

    public function renew($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/mappings');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/mappings');
            return;
        }

        if ($this->mappingModel->renewLease($id, 12)) {
            (new AuditLog())->logAction(Auth::user()['id'], 'mapping_renewed', 'Mapping', $id, ['months' => 12]);
            Session::setFlash('success', 'Lease renewed for 12 months.');
        } else {
            Session::setFlash('error', 'Failed to renew lease.');
        }

        $this->redirect('/admin/mappings');
    }

    public function requestRenewal($id) {
        Auth::requireUser();
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/user/dashboard');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/user/dashboard');
            return;
        }

        $requestedEndDate = trim($_POST['requested_end_date'] ?? '');
        if (empty($requestedEndDate) || strtotime($requestedEndDate) === false) {
            Session::setFlash('error', 'Please provide a valid requested end date.');
            $this->redirect('/user/dashboard');
            return;
        }

        $mapping = $this->mappingModel->findById($id);
        if (!$mapping || (int)$mapping['user_id'] !== (int)$user['id']) {
            Session::setFlash('error', 'Stall allocation not found.');
            $this->redirect('/user/dashboard');
            return;
        }

        if ($this->mappingModel->requestRenewal($id, $requestedEndDate)) {
            (new AuditLog())->logAction($user['id'], 'mapping_renewal_requested', 'Mapping', $id, ['requested_end_date' => $requestedEndDate]);
            Session::setFlash('success', 'Lease renewal request submitted to admin.');
        } else {
            Session::setFlash('error', 'Failed to submit renewal request.');
        }

        $this->redirect('/user/dashboard');
    }

    public function approveRenewal($id) {
        Auth::requireStaffOrAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/mappings');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/mappings');
            return;
        }

        if ($this->mappingModel->approveRenewal($id)) {
            (new AuditLog())->logAction(Auth::user()['id'], 'mapping_renewal_approved', 'Mapping', $id);
            Session::setFlash('success', 'Lease renewal approved.');
        } else {
            Session::setFlash('error', 'Failed to approve renewal.');
        }

        $this->redirect('/admin/mappings');
    }

    public function rejectRenewal($id) {
        Auth::requireStaffOrAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/mappings');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/mappings');
            return;
        }

        if ($this->mappingModel->rejectRenewal($id)) {
            (new AuditLog())->logAction(Auth::user()['id'], 'mapping_renewal_rejected', 'Mapping', $id);
            Session::setFlash('success', 'Lease renewal request rejected.');
        } else {
            Session::setFlash('error', 'Failed to reject renewal.');
        }

        $this->redirect('/admin/mappings');
    }

    private function validateMappingData(array $data): array {
        $errors = [];

        if (empty($data['user_id']) || empty($data['stall_id']) || empty($data['start_date']) || empty($data['end_date'])) {
            $errors[] = 'All required fields must be filled.';
            return $errors;
        }

        if (strtotime($data['start_date']) === false || strtotime($data['end_date']) === false) {
            $errors[] = 'Invalid start or end date.';
            return $errors;
        }

        if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
            $errors[] = 'End date must be after start date.';
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

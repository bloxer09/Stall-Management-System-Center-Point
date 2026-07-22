<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\Mapping;
use App\Models\AuditLog;

class StallMapController extends Controller {
    private $stallModel;
    private $locationModel;
    private $mappingModel;

    public function __construct() {
        $this->stallModel = new Stall();
        $this->locationModel = new StallLocation();
        $this->mappingModel = new Mapping();
    }

    public function adminMap() {
        Auth::requireStaffOrAdmin();

        $locations = $this->locationModel->getAll();
        
        $locationId = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
        if ($locationId === 0 && !empty($locations)) {
            $locationId = (int)$locations[0]['id'];
        }

        $currentLocation = null;
        foreach ($locations as $loc) {
            if ((int)$loc['id'] === $locationId) {
                $currentLocation = $loc;
                break;
            }
        }

        $stalls = [];
        if ($locationId > 0) {
            $stalls = $this->stallModel->getStallsByLocation($locationId);
        }

        $activeMappings = $this->mappingModel->getActiveMappingsByStalls();

        $this->view('pages/admin/stalls/map', [
            'title' => 'Visual Stall Layout Map',
            'locations' => $locations,
            'locationId' => $locationId,
            'currentLocation' => $currentLocation,
            'stalls' => $stalls,
            'activeMappings' => $activeMappings,
        ]);
    }

    public function updateLayout() {
        Auth::requireStaffOrAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        // Accept CSRF from POST body or headers
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid security token'], 403);
        }

        $stallId = isset($_POST['stall_id']) ? (int)$_POST['stall_id'] : 0;
        $gridRow = (isset($_POST['grid_row']) && $_POST['grid_row'] !== '') ? (int)$_POST['grid_row'] : null;
        $gridCol = (isset($_POST['grid_col']) && $_POST['grid_col'] !== '') ? (int)$_POST['grid_col'] : null;

        $stall = $this->stallModel->findById($stallId);
        if (!$stall) {
            $this->jsonResponse(['success' => false, 'error' => 'Stall not found'], 404);
        }

        $ok = $this->stallModel->updateLayout($stallId, $gridRow, $gridCol);
        if ($ok) {
            (new AuditLog())->logAction(Auth::user()['id'], 'stall_layout_updated', 'Stall', $stallId, [
                'stall_number' => $stall['stall_number'],
                'grid_row' => $gridRow,
                'grid_col' => $gridCol
            ]);
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Database update failed'], 500);
        }
    }

    public function userMap() {
        Auth::requireLogin();

        $locations = $this->locationModel->getAll();
        
        $locationId = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
        if ($locationId === 0 && !empty($locations)) {
            $locationId = (int)$locations[0]['id'];
        }

        $currentLocation = null;
        foreach ($locations as $loc) {
            if ((int)$loc['id'] === $locationId) {
                $currentLocation = $loc;
                break;
            }
        }

        $stalls = [];
        if ($locationId > 0) {
            $stalls = $this->stallModel->getStallsByLocation($locationId);
        }

        $activeMappings = $this->mappingModel->getActiveMappingsByStalls();

        $this->view('pages/user/stalls/map', [
            'title' => 'Stall Directory Map',
            'locations' => $locations,
            'locationId' => $locationId,
            'currentLocation' => $currentLocation,
            'stalls' => $stalls,
            'activeMappings' => $activeMappings,
        ]);
    }
}

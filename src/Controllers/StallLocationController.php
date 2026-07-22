<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\StallLocation;

class StallLocationController extends Controller
{
    private $locationModel;

    public function __construct()
    {
        Auth::requireStaffOrAdmin();
        $this->locationModel = new StallLocation();
    }

    public function index()
    {
        $locations = $this->locationModel->getAll();

        return $this->view('pages/admin/stall_locations/index', [
            'title' => 'Stall Locations',
            'locations' => $locations,
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/stall-locations');
                return;
            }

            $name = trim((string)($_POST['name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $gridRows = isset($_POST['grid_rows']) ? (int)$_POST['grid_rows'] : 6;
            $gridCols = isset($_POST['grid_cols']) ? (int)$_POST['grid_cols'] : 8;

            if ($name === '') {
                Session::setFlash('error', 'Location name is required.');
            } elseif ($this->locationModel->create([
                'name' => $name,
                'description' => $description,
                'sort_order' => $sortOrder,
                'grid_rows' => $gridRows,
                'grid_cols' => $gridCols,
            ])) {
                Session::setFlash('success', 'Location created successfully.');
            } else {
                Session::setFlash('error', 'Failed to create location.');
            }
            $this->redirect('/admin/stall-locations');
            return;
        }

        return $this->view('pages/admin/stall_locations/form', [
            'title' => 'Create Location',
            'location' => null,
        ]);
    }

    public function edit($id)
    {
        $location = $this->locationModel->findById($id);
        if (!$location) {
            Session::setFlash('error', 'Location not found.');
            $this->redirect('/admin/stall-locations');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/stall-locations');
                return;
            }

            $name = trim((string)($_POST['name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $gridRows = isset($_POST['grid_rows']) ? (int)$_POST['grid_rows'] : 6;
            $gridCols = isset($_POST['grid_cols']) ? (int)$_POST['grid_cols'] : 8;

            if ($name === '') {
                Session::setFlash('error', 'Location name is required.');
            } elseif ($this->locationModel->update($id, [
                'name' => $name,
                'description' => $description,
                'sort_order' => $sortOrder,
                'grid_rows' => $gridRows,
                'grid_cols' => $gridCols,
            ])) {
                Session::setFlash('success', 'Location updated successfully.');
            } else {
                Session::setFlash('error', 'Failed to update location.');
            }
            $this->redirect('/admin/stall-locations');
            return;
        }

        return $this->view('pages/admin/stall_locations/form', [
            'title' => 'Edit Location',
            'location' => $location,
        ]);
    }

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/stall-locations');
                return;
            }

            if ($this->locationModel->delete($id)) {
                Session::setFlash('success', 'Location deleted successfully.');
            } else {
                Session::setFlash('error', 'Cannot delete location while stalls are assigned to it.');
            }
        }

        $this->redirect('/admin/stall-locations');
    }
}


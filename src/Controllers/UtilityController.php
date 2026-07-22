<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\UtilityReading;
use App\Models\Stall;
use App\Models\AuditLog;
use App\Config\App;

class UtilityController extends Controller
{
    private $utilityModel;
    private $stallModel;

    public function __construct()
    {
        Auth::requireStaffOrAdmin();
        $this->utilityModel = new UtilityReading();
        $this->stallModel = new Stall();
    }

    public function index()
    {
        $readings = $this->utilityModel->getAll();
        $stalls = $this->stallModel->getPaginated(1, 2000, '', 's.stall_number', 'ASC')['data'];

        return $this->view('pages/admin/utilities/index', [
            'title' => App::pageTitle('Metered Utility Readings'),
            'readings' => $readings,
            'stalls' => $stalls
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/utilities');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/utilities');
            return;
        }

        $stallId = (int)($_POST['stall_id'] ?? 0);
        $type = $_POST['type'] ?? 'electricity';
        $currentReading = (float)($_POST['current_reading'] ?? 0);
        $ratePerUnit = (float)($_POST['rate_per_unit'] ?? 0);
        $readingDate = $_POST['reading_date'] ?? date('Y-m-d');

        if ($stallId <= 0 || $currentReading <= 0 || $ratePerUnit <= 0) {
            Session::setFlash('error', 'Please fill in all required utility meter reading fields.');
            $this->redirect('/admin/utilities');
            return;
        }

        $latest = $this->utilityModel->getLatestReading($stallId, $type);
        $previousReading = $latest ? (float)$latest['current_reading'] : 0.0;

        $success = $this->utilityModel->create([
            'stall_id'         => $stallId,
            'type'             => $type,
            'previous_reading' => $previousReading,
            'current_reading'  => $currentReading,
            'rate_per_unit'    => $ratePerUnit,
            'reading_date'     => $readingDate
        ]);

        if ($success) {
            (new AuditLog())->logAction(Auth::user()['id'], 'create_utility_reading', 'UtilityReading', $stallId, [
                'type' => $type,
                'current_reading' => $currentReading,
                'rate_per_unit' => $ratePerUnit
            ]);
            Session::setFlash('success', 'Meter reading logged successfully.');
        } else {
            Session::setFlash('error', 'Failed to log meter reading.');
        }

        $this->redirect('/admin/utilities');
    }
}

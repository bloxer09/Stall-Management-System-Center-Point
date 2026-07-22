<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller {
    private $auditLogModel;

    public function __construct() {
        Auth::requireStaffOrAdmin();
        $this->auditLogModel = new AuditLog();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 50;

        $filters = [
            'search' => trim((string)($_GET['search'] ?? '')),
            'action' => trim((string)($_GET['action'] ?? '')),
            'user_id' => trim((string)($_GET['user_id'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
        ];

        $result = $this->auditLogModel->getPaginated($page, $limit, $filters);

        return $this->view('pages/admin/audit_logs/index', [
            'title' => 'System Audit Logs',
            'logs' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'total' => $result['total'],
            'filters' => $filters,
            'actions' => $this->auditLogModel->getActions(),
            'sortBy' => '',
            'sortOrder' => '',
        ]);
    }

    public function exportCsv() {
        Auth::requireStaffOrAdmin();

        $filters = [
            'search' => trim((string)($_GET['search'] ?? '')),
            'action' => trim((string)($_GET['action'] ?? '')),
            'user_id' => trim((string)($_GET['user_id'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
        ];

        $logs = $this->auditLogModel->getFilteredLogs($filters, 20000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=audit_logs_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Timestamp', 'User Name', 'User Email', 'Action', 'Entity Type', 'Entity ID', 'IP', 'Details']);

        foreach ($logs as $log) {
            fputcsv($out, [
                $log['created_at'],
                $log['user_name'] ?? 'System / Guest',
                $log['user_email'] ?? '',
                $log['action'],
                $log['entity_type'] ?? '',
                $log['entity_id'] ?? '',
                $log['ip_address'] ?? '',
                $log['details'] ?? '',
            ]);
        }

        fclose($out);
        exit();
    }
}

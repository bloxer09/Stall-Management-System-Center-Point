<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Services\ErrorLogService;
use Throwable;

class ErrorLogController extends Controller
{
    private ErrorLogService $errorLogService;

    public function __construct()
    {
        Auth::requireStaffOrAdmin();
        $this->errorLogService = new ErrorLogService();
    }

    public function index()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = 25;

        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'file' => trim((string) ($_GET['file'] ?? '')),
            'level' => trim((string) ($_GET['level'] ?? '')),
            'view' => $this->normalizeView((string) ($_GET['view'] ?? 'grouped')),
            'sort' => $this->normalizeSort((string) ($_GET['sort'] ?? 'timestamp')),
            'order' => $this->normalizeOrder((string) ($_GET['order'] ?? 'DESC')),
        ];

        $result = $this->errorLogService->getPaginated($page, $limit, $filters);

        return $this->view('pages/admin/error_logs/index', [
            'title' => 'Error Logs',
            'logs' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'total' => $result['total'],
            'filters' => $filters,
            'availableFiles' => $this->errorLogService->getLogFileNames(),
            'availableLevels' => $this->errorLogService->getLevels(),
            'sortBy' => $filters['sort'],
            'sortOrder' => $filters['order'],
        ]);
    }

    public function delete()
    {
        Auth::requireAdmin();

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid request. Please try again.');
            $this->redirectToIndex();
        }

        $ids = $this->extractIds($_POST['ids'] ?? '');
        if ($ids === []) {
            Session::setFlash('warning', 'Select at least one log entry to delete.');
            $this->redirectToIndex();
        }

        try {
            $deleted = $this->errorLogService->deleteEntries($ids);

            if ($deleted > 0) {
                (new AuditLog())->logAction(Auth::user()['id'], 'error_log_entries_deleted', 'ErrorLog', null, [
                    'count' => $deleted,
                ]);
                Session::setFlash('success', $deleted . ' error log entr' . ($deleted === 1 ? 'y was' : 'ies were') . ' deleted.');
            } else {
                Session::setFlash('warning', 'The selected log entries were not found. The log file may have already changed.');
            }
        } catch (Throwable $exception) {
            error_log('Error log delete failed: ' . $exception->getMessage());
            Session::setFlash('error', 'Unable to delete the selected log entries right now.');
        }

        $this->redirectToIndex();
    }

    public function clear()
    {
        Auth::requireAdmin();

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid request. Please try again.');
            $this->redirectToIndex();
        }

        try {
            $clearedFiles = $this->errorLogService->clearLogs();

            if ($clearedFiles > 0) {
                (new AuditLog())->logAction(Auth::user()['id'], 'error_logs_cleared', 'ErrorLog', null, [
                    'files' => $clearedFiles,
                ]);
                Session::setFlash('success', 'Cleared ' . $clearedFiles . ' log file' . ($clearedFiles === 1 ? '' : 's') . '.');
            } else {
                Session::setFlash('info', 'There were no log entries to clear.');
            }
        } catch (Throwable $exception) {
            error_log('Error log clear failed: ' . $exception->getMessage());
            Session::setFlash('error', 'Unable to clear the error logs right now.');
        }

        $this->redirectToIndex();
    }

    private function extractIds($rawIds): array
    {
        $values = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
        $ids = [];

        foreach ($values as $value) {
            $value = strtolower(trim((string) $value));
            if (preg_match('/^[a-f0-9]{40}$/', $value) === 1) {
                $ids[$value] = true;
            }
        }

        return array_keys($ids);
    }

    private function normalizeSort(string $sort): string
    {
        $allowed = ['timestamp', 'file', 'level', 'message', 'count'];
        return in_array($sort, $allowed, true) ? $sort : 'timestamp';
    }

    private function normalizeOrder(string $order): string
    {
        return strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    }

    private function normalizeView(string $view): string
    {
        return strtolower($view) === 'raw' ? 'raw' : 'grouped';
    }

    private function redirectToIndex()
    {
        $query = trim((string) ($_POST['return_query'] ?? ''));
        if ($query !== '' && preg_match('/^[A-Za-z0-9_=&%+.,:\-]*$/', $query) === 1) {
            $this->redirect('/admin/error-logs?' . $query);
        }

        $this->redirect('/admin/error-logs');
    }
}

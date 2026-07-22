<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\AuditLog;
use App\Config\App;
use App\Config\Database;
use PDO;

class BackupController extends Controller
{
    private $backupDir;

    public function __construct()
    {
        $this->backupDir = __DIR__ . '/../../storage/backups';
        if (!file_exists($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
    }

    public function index()
    {
        Auth::requireAdmin();

        $files = glob($this->backupDir . '/*.sql');
        $backups = [];

        if ($files) {
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size'     => filesize($file),
                    'created'  => filemtime($file)
                ];
            }
            usort($backups, fn($a, $b) => $b['created'] <=> $a['created']);
        }

        return $this->view('pages/admin/backups/index', [
            'title'   => App::pageTitle('Database Backups'),
            'backups' => $backups
        ]);
    }

    public function create()
    {
        Auth::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/settings/backups');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/settings/backups');
            return;
        }

        try {
            $db = Database::getInstance();
            $dbname = $_ENV['DB_NAME'] ?? 'stallm';

            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $return = "-- STALLM Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $return .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $result = $db->query("SELECT * FROM `$table`");
                $numFields = $result->columnCount();

                $return .= "DROP TABLE IF EXISTS `$table`;\n";
                $row2 = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
                $return .= $row2[1] . ";\n\n";

                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $return .= "INSERT INTO `$table` VALUES(";
                    for ($j = 0; $j < $numFields; $j++) {
                        if (isset($row[$j])) {
                            $escaped = addslashes($row[$j]);
                            $escaped = str_replace("\n", "\\n", $escaped);
                            $return .= '"' . $escaped . '"';
                        } else {
                            $return .= 'NULL';
                        }
                        if ($j < ($numFields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
                $return .= "\n\n";
            }

            $return .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $filename = 'backup_stallm_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            file_put_contents($filepath, $return);

            $adminUser = Auth::user();
            (new AuditLog())->logAction($adminUser['id'], 'create_backup', 'Database', null, ['filename' => $filename]);

            Session::setFlash('success', "Database backup created successfully: {$filename}");
        } catch (\Exception $e) {
            Session::setFlash('error', "Backup failed: " . $e->getMessage());
        }

        $this->redirect('/admin/settings/backups');
    }

    public function download($filename)
    {
        Auth::requireAdmin();

        $cleanFilename = basename($filename);
        $filepath = $this->backupDir . '/' . $cleanFilename;

        if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'sql') {
            Session::setFlash('error', 'Backup file not found.');
            $this->redirect('/admin/settings/backups');
            return;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $cleanFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    public function delete($filename)
    {
        Auth::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/settings/backups');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/settings/backups');
            return;
        }

        $cleanFilename = basename($filename);
        $filepath = $this->backupDir . '/' . $cleanFilename;

        if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
            unlink($filepath);
            Session::setFlash('success', "Backup {$cleanFilename} deleted.");
        } else {
            Session::setFlash('error', 'Backup file not found.');
        }

        $this->redirect('/admin/settings/backups');
    }
}

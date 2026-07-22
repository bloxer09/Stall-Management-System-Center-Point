<?php

namespace App\Models;

use App\Config\Database;
use DateTime;
use DateTimeInterface;
use PDO;

class Payment {
    private $db;
    private ?bool $hasLocationSchema = null;
    private ?bool $hasStallLocationColumn = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function hasStallLocationColumn(): bool
    {
        if ($this->hasStallLocationColumn !== null) {
            return $this->hasStallLocationColumn;
        }

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) AS total
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'stalls'
                  AND column_name = 'location_id'
            ");
            $this->hasStallLocationColumn = (int)($stmt->fetch()['total'] ?? 0) > 0;
        } catch (\Throwable $e) {
            $this->hasStallLocationColumn = false;
        }

        return $this->hasStallLocationColumn;
    }

    private function supportsLocationSchema(): bool
    {
        if ($this->hasLocationSchema !== null) {
            return $this->hasLocationSchema;
        }

        try {
            $tableStmt = $this->db->query("
                SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'stall_locations'
            ");
            $hasLocationTable = (int)($tableStmt->fetch()['total'] ?? 0) > 0;

            $this->hasLocationSchema = $hasLocationTable && $this->hasStallLocationColumn();
        } catch (\Throwable $e) {
            $this->hasLocationSchema = false;
        }

        return $this->hasLocationSchema;
    }

    public function findById($id) {
        $withLocation = $this->supportsLocationSchema();
        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $stmt = $this->db->prepare("
            SELECT p.*, u.name as user_name, u.email as user_email, s.stall_number, {$locationSelect}
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN stalls s ON p.stall_id = s.id
            {$locationJoin}
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByPaymongoId($paymongoId) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE paymongo_id = :paymongo_id LIMIT 1");
        $stmt->execute(['paymongo_id' => $paymongoId]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("\n            INSERT INTO payments (user_id, stall_id, amount, status, paymongo_id, payment_method, payment_reference, payment_notes, paid_at, due_date)\n            VALUES (:user_id, :stall_id, :amount, :status, :paymongo_id, :payment_method, :payment_reference, :payment_notes, :paid_at, :due_date)\n        ");
        $stmt->execute([
            'user_id' => (int)$data['user_id'],
            'stall_id' => (int)$data['stall_id'],
            'amount' => (float)$data['amount'],
            'status' => $data['status'] ?? 'pending',
            'paymongo_id' => $data['paymongo_id'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_notes' => $data['payment_notes'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus($id, $status, $paymongo_id = null) {
        $sql = "UPDATE payments SET status = :status";
        $params = ['id' => $id, 'status' => $status];

        if ($paymongo_id !== null && $paymongo_id !== '') {
            $sql .= ", paymongo_id = :paymongo_id";
            $params['paymongo_id'] = $paymongo_id;
        }

        if (($paymongo_id !== null && $paymongo_id !== '') || $status === 'paid') {
            $sql .= ", payment_method = COALESCE(payment_method, :payment_method)";
            $params['payment_method'] = 'online';
        }

        if ($status === 'paid') {
            $sql .= ", paid_at = COALESCE(paid_at, NOW())";
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function recordOfficePayment(int $id, string $paidAt, ?string $reference = null, ?string $notes = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE payments
            SET status = 'paid',
                paymongo_id = NULL,
                payment_method = 'office',
                payment_reference = :payment_reference,
                payment_notes = :payment_notes,
                paid_at = :paid_at
            WHERE id = :id
              AND status != 'paid'
        ");

        $stmt->execute([
            'id' => $id,
            'payment_reference' => $reference !== '' ? $reference : null,
            'payment_notes' => $notes !== '' ? $notes : null,
            'paid_at' => $paidAt,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function markPaidByPaymongoId($paymongoId) {
        $stmt = $this->db->prepare("UPDATE payments SET status = 'paid' WHERE paymongo_id = :paymongo_id AND status != 'paid'");
        return $stmt->execute(['paymongo_id' => $paymongoId]);
    }

    public function getPaginated($page = 1, $limit = 10, $search = '', $sortBy = 'p.created_at', $sortOrder = 'DESC', $userId = null) {
        $offset = ($page - 1) * $limit;
        $withLocation = $this->supportsLocationSchema();

        $allowedSortColumns = ['p.id', 'u.name', 's.stall_number', 'p.amount', 'p.status', 'p.created_at', 'p.due_date', 'p.paid_at', 'p.payment_method'];
        if ($withLocation) {
            $allowedSortColumns[] = 'l.name';
        }
        $sortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : 'p.created_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $query = "
            SELECT p.*, u.name as user_name, s.stall_number, {$locationSelect}
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN stalls s ON p.stall_id = s.id
            {$locationJoin}
            WHERE 1=1
        ";

        $params = [];

        if ($userId) {
            $query .= " AND p.user_id = :user_id";
            $params['user_id'] = (int)$userId;
        }

        if (!empty($search)) {
            $searchConditions = ["u.name LIKE :search", "s.stall_number LIKE :search", "p.paymongo_id LIKE :search", "p.payment_reference LIKE :search", "p.payment_method LIKE :search"];
            if ($withLocation) {
                $searchConditions[] = "l.name LIKE :search";
            }
            $query .= " AND (" . implode(' OR ', $searchConditions) . ")";
            $params['search'] = "%$search%";
        }

        $countQuery = "
            SELECT COUNT(*) as total
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN stalls s ON p.stall_id = s.id
            {$locationJoin}
            WHERE 1=1
        ";
        if ($userId) {
            $countQuery .= " AND p.user_id = :user_id";
        }
        if (!empty($search)) {
            $countSearchConditions = ["u.name LIKE :search", "s.stall_number LIKE :search", "p.paymongo_id LIKE :search", "p.payment_reference LIKE :search", "p.payment_method LIKE :search"];
            if ($withLocation) {
                $countSearchConditions[] = "l.name LIKE :search";
            }
            $countQuery .= " AND (" . implode(' OR ', $countSearchConditions) . ")";
        }
        $countStmt = $this->db->prepare($countQuery);
        if ($userId) {
            $countStmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        }
        if (!empty($search)) {
            $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetch()['total'] ?? 0);

        $query .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        if ($userId) {
            $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        }
        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $records = $stmt->fetchAll();

        return [
            'data' => $records,
            'total' => $total,
            'page' => $page,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 0,
        ];
    }

    public function getActiveUserMappings($user_id) {
        $withLocation = $this->supportsLocationSchema();
        $locationIdSelect = $this->hasStallLocationColumn() ? "s.location_id" : "NULL AS location_id";
        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $stmt = $this->db->prepare("
            SELECT m.*, s.stall_number, s.price, {$locationIdSelect}, {$locationSelect}
            FROM stall_mappings m
            JOIN stalls s ON m.stall_id = s.id
            {$locationJoin}
            WHERE m.user_id = :user_id AND m.is_active = 1
        ");
        $stmt->execute(['user_id' => (int)$user_id]);
        return $stmt->fetchAll();
    }

    public function getPendingCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public function getPaidStallIdsThisMonth($userId) {
        $stmt = $this->db->prepare("\n            SELECT DISTINCT stall_id FROM payments\n            WHERE user_id = :user_id\n              AND status = 'paid'\n              AND (\n                    (due_date IS NOT NULL AND MONTH(due_date) = MONTH(CURRENT_DATE()) AND YEAR(due_date) = YEAR(CURRENT_DATE()))\n                 OR (due_date IS NULL AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()))\n              )\n        ");
        $stmt->execute(['user_id' => (int)$userId]);
        return array_column($stmt->fetchAll(), 'stall_id');
    }

    public function getRevenueStats($period = '1month') {
        $dateColumn = "COALESCE(paid_at, created_at)";
        $condition = "";
        switch ($period) {
            case '7days':
                $condition = "$dateColumn >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
                break;
            case '1year':
                $condition = "YEAR($dateColumn) = YEAR(CURRENT_DATE())";
                break;
            case '1month':
            default:
                $condition = "MONTH($dateColumn) = MONTH(CURRENT_DATE()) AND YEAR($dateColumn) = YEAR(CURRENT_DATE())";
                break;
        }

        $stmt = $this->db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND $condition");
        $result = $stmt->fetch()['total'] ?? 0;
        return $result ? (float)$result : 0.0;
    }

    public function getRevenueChartData($period = '1year') {
        if ($period === '7days') {
            $stmt = $this->db->query("\n                SELECT DATE(COALESCE(paid_at, created_at)) as label_val, SUM(amount) as total\n                FROM payments\n                WHERE status = 'paid' AND COALESCE(paid_at, created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY)\n                GROUP BY DATE(COALESCE(paid_at, created_at))\n                ORDER BY label_val ASC\n            ");
            return ['type' => 'daily', 'data' => $stmt->fetchAll()];
        } elseif ($period === '1month') {
            $stmt = $this->db->query("\n                SELECT DATE(COALESCE(paid_at, created_at)) as label_val, SUM(amount) as total\n                FROM payments\n                WHERE status = 'paid'\n                  AND MONTH(COALESCE(paid_at, created_at)) = MONTH(CURRENT_DATE())\n                  AND YEAR(COALESCE(paid_at, created_at)) = YEAR(CURRENT_DATE())\n                GROUP BY DATE(COALESCE(paid_at, created_at))\n                ORDER BY label_val ASC\n            ");
            return ['type' => 'daily_month', 'data' => $stmt->fetchAll()];
        } else {
            $stmt = $this->db->query("\n                SELECT MONTH(COALESCE(paid_at, created_at)) as label_val, SUM(amount) as total\n                FROM payments\n                WHERE status = 'paid' AND YEAR(COALESCE(paid_at, created_at)) = YEAR(CURRENT_DATE())\n                GROUP BY MONTH(COALESCE(paid_at, created_at))\n                ORDER BY label_val ASC\n            ");
            return ['type' => 'monthly', 'data' => $stmt->fetchAll()];
        }
    }

    public function getDelinquencyRate() {
        $stmt = $this->db->query("\n            SELECT\n                SUM(CASE WHEN status = 'pending' AND due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_count,\n                COUNT(*) AS due_count\n            FROM payments\n            WHERE due_date IS NOT NULL\n              AND due_date <= CURDATE()\n        ");

        $row = $stmt->fetch();
        $overdue = (int)($row['overdue_count'] ?? 0);
        $due = (int)($row['due_count'] ?? 0);

        return [
            'overdue' => $overdue,
            'due_total' => $due,
            'rate' => $due > 0 ? round(($overdue / $due) * 100, 2) : 0,
        ];
    }

    public function getMonthlyRevenueTrend() {
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') as month_label, 
                   SUM(amount) as total
            FROM payments
            WHERE status = 'paid'
            GROUP BY DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m')
            ORDER BY month_label DESC
            LIMIT 12
        ");
        return array_reverse($stmt->fetchAll());
    }

    public function getPaymentMethodBreakdown() {
        $stmt = $this->db->query("
            SELECT COALESCE(payment_method, 'online') as method, COUNT(*) as count, SUM(amount) as total
            FROM payments
            WHERE status = 'paid'
            GROUP BY COALESCE(payment_method, 'online')
        ");
        return $stmt->fetchAll();
    }

    private function computeDueDate(string $startDate, DateTimeInterface $forMonth): string {
        $start = new DateTime($startDate);
        $targetMonth = new DateTime($forMonth->format('Y-m-01'));

        $day = (int)$start->format('d');
        $lastDay = (int)$targetMonth->format('t');
        $adjustedDay = min($day, $lastDay);

        return $targetMonth->format('Y-m-') . str_pad((string)$adjustedDay, 2, '0', STR_PAD_LEFT);
    }

    public function getOrCreateMonthlyInvoice($userId, $stallId, $amount, $startDate, ?DateTimeInterface $forMonth = null) {
        $forMonth = $forMonth ?: new DateTime();
        $dueDate = $this->computeDueDate($startDate, $forMonth);

        $existingStmt = $this->db->prepare("\n            SELECT id\n            FROM payments\n            WHERE user_id = :user_id\n              AND stall_id = :stall_id\n              AND due_date = :due_date\n            LIMIT 1\n        ");
        $existingStmt->execute([
            'user_id' => (int)$userId,
            'stall_id' => (int)$stallId,
            'due_date' => $dueDate,
        ]);

        $existing = $existingStmt->fetch();
        if ($existing) {
            return (int)$existing['id'];
        }

        return $this->create([
            'user_id' => (int)$userId,
            'stall_id' => (int)$stallId,
            'amount' => (float)$amount,
            'status' => 'pending',
            'due_date' => $dueDate,
        ]);
    }

    public function getPendingInvoiceForCheckout($userId, $stallId) {
        $stmt = $this->db->prepare("\n            SELECT *\n            FROM payments\n            WHERE user_id = :user_id\n              AND stall_id = :stall_id\n              AND status = 'pending'\n            ORDER BY COALESCE(due_date, created_at) ASC, created_at ASC\n            LIMIT 1\n        ");

        $stmt->execute([
            'user_id' => (int)$userId,
            'stall_id' => (int)$stallId,
        ]);

        return $stmt->fetch();
    }

    public function generateMonthlyInvoices(?DateTimeInterface $forMonth = null) {
        $forMonth = $forMonth ?: new DateTime();

        $stmt = $this->db->query("
            SELECT m.user_id, m.stall_id, m.start_date, s.price
            FROM stall_mappings m
            JOIN stalls s ON s.id = m.stall_id
            WHERE m.is_active = 1
        ");

        $mappings = $stmt->fetchAll();
        $created = 0;

        if (empty($mappings)) {
            return 0;
        }

        // Fetch all existing payments for the target month in a single query
        $startDate = $forMonth->format('Y-m-01');
        $endDate = $forMonth->format('Y-m-t');

        $payStmt = $this->db->prepare("
            SELECT user_id, stall_id, due_date
            FROM payments
            WHERE due_date >= :start_date AND due_date <= :end_date
        ");
        $payStmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $existingPayments = $payStmt->fetchAll();

        // Create an in-memory lookup map of existing payments
        $lookup = [];
        foreach ($existingPayments as $payment) {
            $key = $payment['user_id'] . '_' . $payment['stall_id'] . '_' . $payment['due_date'];
            $lookup[$key] = true;
        }

        foreach ($mappings as $mapping) {
            $dueDate = $this->computeDueDate((string)$mapping['start_date'], $forMonth);
            $key = $mapping['user_id'] . '_' . $mapping['stall_id'] . '_' . $dueDate;

            if (!isset($lookup[$key])) {
                $this->create([
                    'user_id' => (int)$mapping['user_id'],
                    'stall_id' => (int)$mapping['stall_id'],
                    'amount' => (float)$mapping['price'],
                    'status' => 'pending',
                    'due_date' => $dueDate,
                ]);
                $created++;
                $lookup[$key] = true;
            }
        }

        return $created;
    }

    public function getDueReminders($daysAhead = 7) {
        $withLocation = $this->supportsLocationSchema();
        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $stmt = $this->db->prepare("
            SELECT p.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone, s.stall_number, {$locationSelect}
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN stalls s ON p.stall_id = s.id
            {$locationJoin}
            WHERE p.status = 'pending'
              AND p.due_date IS NOT NULL
              AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY p.due_date ASC
        ");
        $stmt->bindValue(':days', (int)$daysAhead, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUpcomingDues($userId = null) {
        $withLocation = $this->supportsLocationSchema();
        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $sql = "
            SELECT p.*, u.name AS user_name, s.stall_number, {$locationSelect}, DATEDIFF(p.due_date, CURDATE()) AS days_left, p.due_date AS next_due_date
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN stalls s ON p.stall_id = s.id
            {$locationJoin}
            WHERE p.status = 'pending'
              AND p.due_date IS NOT NULL
              AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ";

        $params = [];
        if ($userId !== null) {
            $sql .= " AND p.user_id = :user_id";
            $params['user_id'] = (int)$userId;
        }

        $sql .= " ORDER BY p.due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}

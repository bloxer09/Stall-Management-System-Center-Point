<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class AuditLog {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function logAction($userId, $action, $entityType = null, $entityId = null, $details = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        try {
            $resolvedUserId = $this->resolveExistingUserId($userId);
            $resolvedEntityId = $this->normalizeNullableInt($entityId);
            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)\n                 VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)"
            );

            return $stmt->execute([
                'user_id' => $resolvedUserId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $resolvedEntityId,
                'details' => $details ? json_encode($details) : null,
                'ip_address' => $ipAddress,
            ]);
        } catch (PDOException $e) {
            error_log('Audit log insert failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getPaginated($page = 1, $limit = 50, array $filters = []) {
        $offset = max(0, ($page - 1) * $limit);

        $query = "\n            FROM audit_logs a\n            LEFT JOIN users u ON a.user_id = u.id\n            WHERE 1=1\n        ";

        $params = [];

        if (!empty($filters['action'])) {
            $query .= " AND a.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $query .= " AND a.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(a.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(a.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (u.name LIKE :search OR u.email LIKE :search OR a.action LIKE :search OR a.entity_type LIKE :search OR a.details LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total " . $query);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetch()['total'] ?? 0);

        $dataSql = "\n            SELECT a.*, u.name as user_name, u.email as user_email\n            " . $query . "\n            ORDER BY a.created_at DESC\n            LIMIT :limit OFFSET :offset\n        ";

        $stmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => (int)$page,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 0,
        ];
    }

    public function getActions() {
        $stmt = $this->db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
        return array_column($stmt->fetchAll(), 'action');
    }

    public function getFilteredLogs(array $filters = [], $limit = 5000) {
        $result = $this->getPaginated(1, $limit, $filters);
        return $result['data'];
    }

    private function resolveExistingUserId($userId): ?int {
        $normalizedUserId = $this->normalizeNullableInt($userId);
        if ($normalizedUserId === null) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $normalizedUserId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false ? $normalizedUserId : null;
    }

    private function normalizeNullableInt($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        return (int) $value;
    }
}

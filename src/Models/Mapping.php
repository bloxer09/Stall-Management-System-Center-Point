<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Mapping {
    private $db;
    private ?bool $hasLocationSchema = null;

    public function __construct() {
        $this->db = Database::getInstance();
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

            $columnStmt = $this->db->query("
                SELECT COUNT(*) AS total
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'stalls'
                  AND column_name = 'location_id'
            ");
            $hasLocationColumn = (int)($columnStmt->fetch()['total'] ?? 0) > 0;

            $this->hasLocationSchema = $hasLocationTable && $hasLocationColumn;
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
            SELECT m.*, u.name as user_name, s.stall_number, s.price, {$locationSelect}
            FROM stall_mappings m
            JOIN users u ON m.user_id = u.id
            JOIN stalls s ON m.stall_id = s.id
            {$locationJoin}
            WHERE m.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    private function hasActiveMappingForStall($stallId, $excludeId = null) {
        $sql = "SELECT COUNT(*) AS total FROM stall_mappings WHERE stall_id = :stall_id AND is_active = 1";
        $params = ['stall_id' => $stallId];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)($stmt->fetch()['total'] ?? 0) > 0;
    }

    private function syncStallStatus($stallId) {
        $stmt = $this->db->prepare("\n            SELECT COUNT(*) AS total\n            FROM stall_mappings\n            WHERE stall_id = :stall_id AND is_active = 1\n        ");
        $stmt->execute(['stall_id' => $stallId]);

        $activeCount = (int)($stmt->fetch()['total'] ?? 0);
        $status = $activeCount > 0 ? 'occupied' : 'available';

        $update = $this->db->prepare("UPDATE stalls SET status = :status WHERE id = :stall_id");
        $update->execute([
            'status' => $status,
            'stall_id' => $stallId,
        ]);
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();

            $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            if ($isActive === 1 && $this->hasActiveMappingForStall($data['stall_id'])) {
                throw new \RuntimeException('This stall already has an active assignment.');
            }

            $stmt = $this->db->prepare("\n                INSERT INTO stall_mappings (user_id, stall_id, start_date, end_date, is_active, contract_document)\n                VALUES (:user_id, :stall_id, :start_date, :end_date, :is_active, :contract_document)\n            ");

            $ok = $stmt->execute([
                'user_id' => $data['user_id'],
                'stall_id' => $data['stall_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => $isActive,
                'contract_document' => $data['contract_document'] ?? null,
            ]);

            if (!$ok) {
                throw new \RuntimeException('Failed to create mapping record.');
            }

            $this->syncStallStatus($data['stall_id']);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Mapping create error: ' . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $this->db->beginTransaction();

            $existing = $this->findById($id);
            if (!$existing) {
                throw new \RuntimeException('Mapping not found.');
            }

            $newStallId = (int)$data['stall_id'];
            $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 0;

            if ($isActive === 1 && $this->hasActiveMappingForStall($newStallId, $id)) {
                throw new \RuntimeException('This stall already has an active assignment.');
            }

            $stmt = $this->db->prepare("\n                UPDATE stall_mappings\n                SET user_id = :user_id, stall_id = :stall_id, start_date = :start_date, end_date = :end_date, is_active = :is_active, contract_document = :contract_document\n                WHERE id = :id\n            ");

            $ok = $stmt->execute([
                'id' => $id,
                'user_id' => $data['user_id'],
                'stall_id' => $newStallId,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => $isActive,
                'contract_document' => $data['contract_document'] ?? null,
            ]);

            if (!$ok) {
                throw new \RuntimeException('Failed to update mapping record.');
            }

            $oldStallId = (int)$existing['stall_id'];
            if ($oldStallId !== $newStallId) {
                $this->syncStallStatus($oldStallId);
            }
            $this->syncStallStatus($newStallId);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Mapping update error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $this->db->beginTransaction();

            $mapping = $this->findById($id);
            if (!$mapping) {
                throw new \RuntimeException('Mapping not found.');
            }

            $stmt = $this->db->prepare("DELETE FROM stall_mappings WHERE id = :id");
            $ok = $stmt->execute(['id' => $id]);

            if (!$ok) {
                throw new \RuntimeException('Failed to delete mapping.');
            }

            $this->syncStallStatus($mapping['stall_id']);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Mapping delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function renewLease($id, $months = 12) {
        $mapping = $this->findById($id);
        if (!$mapping) {
            return false;
        }

        $startPoint = new \DateTime($mapping['end_date']);
        $today = new \DateTime('today');
        if ($startPoint < $today) {
            $startPoint = $today;
        }

        $startPoint->modify('+' . (int)$months . ' months');
        $newEndDate = $startPoint->format('Y-m-d');

        $stmt = $this->db->prepare("UPDATE stall_mappings SET end_date = :end_date, is_active = 1 WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'end_date' => $newEndDate,
        ]);
    }

    public function requestRenewal($id, $requestedEndDate) {
        $stmt = $this->db->prepare("UPDATE stall_mappings SET renewal_status = 'requested', requested_end_date = :requested_end_date WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'requested_end_date' => $requestedEndDate
        ]);
    }

    public function approveRenewal($id) {
        $mapping = $this->findById($id);
        if (!$mapping || empty($mapping['requested_end_date'])) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE stall_mappings SET end_date = :end_date, renewal_status = 'approved', requested_end_date = NULL, is_active = 1 WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'end_date' => $mapping['requested_end_date']
        ]);
    }

    public function rejectRenewal($id) {
        $stmt = $this->db->prepare("UPDATE stall_mappings SET renewal_status = 'rejected', requested_end_date = NULL WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getPaginated($page = 1, $limit = 10, $search = '', $sortBy = 'm.created_at', $sortOrder = 'DESC') {
        $offset = ($page - 1) * $limit;
        $withLocation = $this->supportsLocationSchema();

        $allowedSortColumns = ['m.id', 'u.name', 's.stall_number', 'm.start_date', 'm.end_date', 'm.is_active', 'm.created_at'];
        if ($withLocation) {
            $allowedSortColumns[] = 'l.name';
        }
        $sortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : 'm.created_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $query = "
            SELECT m.*, u.name as user_name, s.stall_number, s.price, {$locationSelect}
            FROM stall_mappings m
            JOIN users u ON m.user_id = u.id
            JOIN stalls s ON m.stall_id = s.id
            {$locationJoin}
        ";
        $params = [];

        if (!empty($search)) {
            $conditions = ["u.name LIKE :search", "s.stall_number LIKE :search"];
            if ($withLocation) {
                $conditions[] = "l.name LIKE :search";
            }
            $query .= " WHERE " . implode(' OR ', $conditions);
            $params['search'] = "%$search%";
        }

        $countQuery = "
            SELECT COUNT(*) as total
            FROM stall_mappings m
            JOIN users u ON m.user_id = u.id
            JOIN stalls s ON m.stall_id = s.id
            {$locationJoin}
        ";
        if (!empty($search)) {
            $countConditions = ["u.name LIKE :search", "s.stall_number LIKE :search"];
            if ($withLocation) {
                $countConditions[] = "l.name LIKE :search";
            }
            $countQuery .= " WHERE " . implode(' OR ', $countConditions);
        }

        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = (int)($countStmt->fetch()['total'] ?? 0);

        $query .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
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
            'pages' => $limit > 0 ? ceil($total / $limit) : 0,
        ];
    }

    public function getPagedUserMappings($userId, $page = 1, $limit = 10, $search = '', $sortBy = 's.stall_number', $sortOrder = 'ASC') {
        $offset = ($page - 1) * $limit;
        $withLocation = $this->supportsLocationSchema();

        $allowedSortColumns = ['s.stall_number', 'm.start_date', 'm.end_date', 'm.is_active', 's.price'];
        if ($withLocation) {
            $allowedSortColumns[] = 'l.name';
        }
        $sortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : 's.stall_number';
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $query = "
            SELECT m.*, s.stall_number, s.price, s.description, s.status as stall_status, {$locationSelect}
            FROM stall_mappings m
            JOIN stalls s ON m.stall_id = s.id
            {$locationJoin}
            WHERE m.user_id = :user_id
        ";

        if (!empty($search)) {
            $searchConditions = ["s.stall_number LIKE :search", "s.description LIKE :search"];
            if ($withLocation) {
                $searchConditions[] = "l.name LIKE :search";
            }
            $query .= " AND (" . implode(' OR ', $searchConditions) . ")";
        }

        $countQuery = "
            SELECT COUNT(*) as total
            FROM stall_mappings m
            JOIN stalls s ON m.stall_id = s.id
            {$locationJoin}
            WHERE m.user_id = :user_id
        ";
        if (!empty($search)) {
            $countSearchConditions = ["s.stall_number LIKE :search", "s.description LIKE :search"];
            if ($withLocation) {
                $countSearchConditions[] = "l.name LIKE :search";
            }
            $countQuery .= " AND (" . implode(' OR ', $countSearchConditions) . ")";
        }

        $countStmt = $this->db->prepare($countQuery);
        $countStmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        if (!empty($search)) {
            $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetch()['total'] ?? 0);

        $query .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
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
            'pages' => $limit > 0 ? ceil($total / $limit) : 0,
        ];
    }

    public function getExpiringLeases($days = 30) {
        $withLocation = $this->supportsLocationSchema();
        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $query = "
            SELECT m.*, u.name as user_name, s.stall_number, {$locationSelect}
            FROM stall_mappings m
            JOIN users u ON m.user_id = u.id
            JOIN stalls s ON m.stall_id = s.id
            {$locationJoin}
            WHERE m.is_active = 1
            AND m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY m.end_date ASC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActiveMappingsByStalls() {
        $stmt = $this->db->query("
            SELECT m.*, u.name as user_name, u.email as user_email, u.phone as user_phone
            FROM stall_mappings m
            JOIN users u ON m.user_id = u.id
            WHERE m.is_active = 1
        ");
        $mappings = $stmt->fetchAll();
        $indexed = [];
        foreach ($mappings as $m) {
            $indexed[(int)$m['stall_id']] = $m;
        }
        return $indexed;
    }
}

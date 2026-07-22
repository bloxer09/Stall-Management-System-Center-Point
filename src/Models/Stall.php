<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Stall {
    private $db;
    private ?bool $hasLocationSchema = null;
    private ?bool $hasLocationColumn = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function hasLocationColumn(): bool
    {
        if ($this->hasLocationColumn !== null) {
            return $this->hasLocationColumn;
        }

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) AS total
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'stalls'
                  AND column_name = 'location_id'
            ");
            $this->hasLocationColumn = (int)($stmt->fetch()['total'] ?? 0) > 0;
        } catch (\Throwable $e) {
            $this->hasLocationColumn = false;
        }

        return $this->hasLocationColumn;
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
            $this->hasLocationSchema = $hasLocationTable && $this->hasLocationColumn();
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
            SELECT s.*, {$locationSelect}
            FROM stalls s
            {$locationJoin}
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByNumber($stall_number) {
        $withLocation = $this->supportsLocationSchema();
        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $stmt = $this->db->prepare("
            SELECT s.*, {$locationSelect}
            FROM stalls s
            {$locationJoin}
            WHERE s.stall_number = :stall_number
        ");
        $stmt->execute(['stall_number' => $stall_number]);
        return $stmt->fetch();
    }

    public function create($data) {
        if ($this->hasLocationColumn()) {
            $stmt = $this->db->prepare("
                INSERT INTO stalls (stall_number, status, price, description, image, location_id, grid_row, grid_col)
                VALUES (:stall_number, :status, :price, :description, :image, :location_id, :grid_row, :grid_col)
            ");

            return $stmt->execute([
                'stall_number' => $data['stall_number'],
                'status' => $data['status'] ?? 'available',
                'price' => $data['price'],
                'description' => $data['description'] ?? null,
                'image' => $data['image'] ?? null,
                'location_id' => !empty($data['location_id']) ? (int)$data['location_id'] : null,
                'grid_row' => isset($data['grid_row']) && $data['grid_row'] !== '' ? (int)$data['grid_row'] : null,
                'grid_col' => isset($data['grid_col']) && $data['grid_col'] !== '' ? (int)$data['grid_col'] : null,
            ]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO stalls (stall_number, status, price, description, image, grid_row, grid_col)
            VALUES (:stall_number, :status, :price, :description, :image, :grid_row, :grid_col)
        ");

        return $stmt->execute([
            'stall_number' => $data['stall_number'],
            'status' => $data['status'] ?? 'available',
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'grid_row' => isset($data['grid_row']) && $data['grid_row'] !== '' ? (int)$data['grid_row'] : null,
            'grid_col' => isset($data['grid_col']) && $data['grid_col'] !== '' ? (int)$data['grid_col'] : null,
        ]);
    }

    public function update($id, $data) {
        $params = [
            'id' => $id,
            'stall_number' => $data['stall_number'],
            'status' => $data['status'],
            'price' => $data['price'],
            'description' => $data['description'],
            'grid_row' => isset($data['grid_row']) && $data['grid_row'] !== '' ? (int)$data['grid_row'] : null,
            'grid_col' => isset($data['grid_col']) && $data['grid_col'] !== '' ? (int)$data['grid_col'] : null,
        ];

        $sql = "UPDATE stalls SET stall_number = :stall_number, status = :status, price = :price, description = :description, grid_row = :grid_row, grid_col = :grid_col";
        if ($this->hasLocationColumn()) {
            $sql .= ", location_id = :location_id";
            $params['location_id'] = !empty($data['location_id']) ? (int)$data['location_id'] : null;
        }

        if (array_key_exists('image', $data)) {
            $sql .= ", image = :image";
            $params['image'] = $data['image'];
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateLayout($id, $grid_row, $grid_col) {
        $stmt = $this->db->prepare("UPDATE stalls SET grid_row = :grid_row, grid_col = :grid_col WHERE id = :id");
        return $stmt->execute([
            'id' => (int)$id,
            'grid_row' => $grid_row !== null && $grid_row !== '' ? (int)$grid_row : null,
            'grid_col' => $grid_col !== null && $grid_col !== '' ? (int)$grid_col : null,
        ]);
    }

    public function getStallsByLocation($locationId) {
        $stmt = $this->db->prepare("
            SELECT s.*
            FROM stalls s
            WHERE s.location_id = :location_id
            ORDER BY s.stall_number ASC
        ");
        $stmt->execute(['location_id' => $locationId]);
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE stalls SET status = :status WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'status' => $status
        ]);
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM stalls WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log('Stall delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function bulkUpdateStatus($ids, $status) {
        $ids = array_values(array_filter(array_map('intval', (array)$ids)));
        if (empty($ids)) {
            return false;
        }

        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE stalls SET status = ? WHERE id IN ($inQuery)");

        $params = array_merge([$status], $ids);
        return $stmt->execute($params);
    }

    public function bulkDelete($ids) {
        $ids = array_values(array_filter(array_map('intval', (array)$ids)));
        if (empty($ids)) {
            return false;
        }

        try {
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("DELETE FROM stalls WHERE id IN ($inQuery)");
            return $stmt->execute($ids);
        } catch (PDOException $e) {
            error_log('Stall bulk delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getStallsWithPaymentHistory(array $ids): array {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT DISTINCT s.id, s.stall_number
            FROM stalls s
            INNER JOIN payments p ON p.stall_id = s.id
            WHERE s.id IN ($inQuery)
            ORDER BY s.stall_number ASC
        ");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function getPaginated($page = 1, $limit = 10, $search = '', $sortBy = 's.stall_number', $sortOrder = 'ASC') {
        $offset = max(0, ($page - 1) * $limit);
        $withLocation = $this->supportsLocationSchema();

        $allowedSortColumns = ['s.id', 's.stall_number', 's.status', 's.price', 's.created_at'];
        if ($withLocation) {
            $allowedSortColumns[] = 'l.name';
        }
        $sortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : 's.stall_number';
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";
        $baseSql = "
            FROM stalls s
            {$locationJoin}
        ";

        $where = '';
        $params = [];
        if (!empty($search)) {
            $searchConditions = ["s.stall_number LIKE :search", "s.description LIKE :search"];
            if ($withLocation) {
                $searchConditions[] = "l.name LIKE :search";
            }
            $where = " WHERE " . implode(' OR ', $searchConditions);
            $params['search'] = "%{$search}%";
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total " . $baseSql . $where);
        if (!empty($search)) {
            $countStmt->bindValue(':search', "%{$search}%", PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetch()['total'] ?? 0);

        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $query = "
            SELECT s.*, {$locationSelect}
            " . $baseSql . $where . "
            ORDER BY {$sortBy} {$sortOrder}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($query);
        if (!empty($search)) {
            $stmt->bindValue(':search', "%{$search}%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $records = $stmt->fetchAll();

        return [
            'data' => $records,
            'total' => $total,
            'page' => (int)$page,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 0,
        ];
    }

    public function getTotal() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM stalls");
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public function getOccupiedCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM stalls WHERE status = 'occupied'");
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public function getAvailableStalls($limit = 6) {
        $withLocation = $this->supportsLocationSchema();
        $locationSelect = $withLocation ? "l.name AS location_name" : "NULL AS location_name";
        $locationJoin = $withLocation ? "LEFT JOIN stall_locations l ON s.location_id = l.id" : "";

        $stmt = $this->db->prepare("
            SELECT s.*, {$locationSelect}
            FROM stalls s
            {$locationJoin}
            WHERE s.status = 'available'
            ORDER BY s.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

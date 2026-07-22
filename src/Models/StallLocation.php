<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class StallLocation
{
    private $db;
    private ?bool $hasLocationTable = null;
    private ?bool $hasStallLocationColumn = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function hasLocationTable(): bool
    {
        if ($this->hasLocationTable !== null) {
            return $this->hasLocationTable;
        }

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'stall_locations'
            ");
            $this->hasLocationTable = (int)($stmt->fetch()['total'] ?? 0) > 0;
        } catch (\Throwable $e) {
            $this->hasLocationTable = false;
        }

        return $this->hasLocationTable;
    }

    private function hasStallsLocationColumn(): bool
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

    public function getAll()
    {
        if (!$this->hasLocationTable()) {
            return [];
        }
        $stmt = $this->db->query("SELECT * FROM stall_locations ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll();
    }

    public function findById($id)
    {
        if (!$this->hasLocationTable()) {
            return false;
        }
        $stmt = $this->db->prepare("SELECT * FROM stall_locations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data)
    {
        if (!$this->hasLocationTable()) {
            return false;
        }
        $stmt = $this->db->prepare("
            INSERT INTO stall_locations (name, description, sort_order, grid_rows, grid_cols)
            VALUES (:name, :description, :sort_order, :grid_rows, :grid_cols)
        ");

        return $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
            'grid_rows' => isset($data['grid_rows']) ? (int)$data['grid_rows'] : 6,
            'grid_cols' => isset($data['grid_cols']) ? (int)$data['grid_cols'] : 8,
        ]);
    }

    public function update($id, array $data)
    {
        if (!$this->hasLocationTable()) {
            return false;
        }
        $stmt = $this->db->prepare("
            UPDATE stall_locations
            SET name = :name, description = :description, sort_order = :sort_order, grid_rows = :grid_rows, grid_cols = :grid_cols
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
            'grid_rows' => isset($data['grid_rows']) ? (int)$data['grid_rows'] : 6,
            'grid_cols' => isset($data['grid_cols']) ? (int)$data['grid_cols'] : 8,
        ]);
    }

    public function delete($id)
    {
        if (!$this->hasLocationTable()) {
            return false;
        }

        // Prevent deletion while stalls are still assigned to this location.
        if ($this->hasStallsLocationColumn()) {
            $check = $this->db->prepare("SELECT COUNT(*) AS total FROM stalls WHERE location_id = :id");
            $check->execute(['id' => $id]);
            $count = (int)($check->fetch()['total'] ?? 0);
            if ($count > 0) {
                return false;
            }
        }

        $stmt = $this->db->prepare("DELETE FROM stall_locations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

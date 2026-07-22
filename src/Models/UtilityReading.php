<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class UtilityReading
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): bool
    {
        $previousReading = (float)($data['previous_reading'] ?? 0);
        $currentReading = (float)$data['current_reading'];
        $ratePerUnit = (float)$data['rate_per_unit'];

        $consumption = max(0, $currentReading - $previousReading);
        $totalAmount = $consumption * $ratePerUnit;

        $sql = "INSERT INTO utility_readings (stall_id, type, previous_reading, current_reading, rate_per_unit, total_amount, reading_date) 
                VALUES (:stall_id, :type, :previous_reading, :current_reading, :rate_per_unit, :total_amount, :reading_date)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'stall_id'         => (int)$data['stall_id'],
            'type'             => $data['type'],
            'previous_reading' => $previousReading,
            'current_reading'  => $currentReading,
            'rate_per_unit'    => $ratePerUnit,
            'total_amount'     => $totalAmount,
            'reading_date'     => $data['reading_date'] ?? date('Y-m-d')
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT u.*, s.stall_number 
                FROM utility_readings u
                JOIN stalls s ON u.stall_id = s.id
                ORDER BY u.reading_date DESC, u.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestReading(int $stallId, string $type): ?array
    {
        $sql = "SELECT * FROM utility_readings 
                WHERE stall_id = :stall_id AND type = :type 
                ORDER BY reading_date DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['stall_id' => $stallId, 'type' => $type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

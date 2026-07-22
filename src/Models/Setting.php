<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Setting {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function get($key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    }

    public function set($key, $value) {
        // Upsert logic (Insert or Update on Duplicate Key)
        // Works in MySQL >= 5.7
        $stmt = $this->db->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_at) 
            VALUES (:key, :insert_value, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = :update_value, updated_at = NOW()
        ");
        
        return $stmt->execute([
            'key' => $key,
            'insert_value' => $value,
            'update_value' => $value,
        ]);
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $results = $stmt->fetchAll();
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}

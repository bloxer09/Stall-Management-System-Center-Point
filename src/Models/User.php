<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;
use Throwable;

class User {
    private $db;
    private ?bool $hasPhoneColumn = null;
    private ?bool $hasEmailVerifiedAtColumn = null;
    private ?bool $hasPhoneVerifiedAtColumn = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findByGoogleId($googleId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = :google_id");
        $stmt->execute(['google_id' => $googleId]);
        return $stmt->fetch();
    }

    public function linkGoogleProvider($userId, $googleId) {
        $stmt = $this->db->prepare("UPDATE users SET google_id = :google_id, email_verified_at = IFNULL(email_verified_at, CURRENT_TIMESTAMP) WHERE id = :id");
        return $stmt->execute(['google_id' => $googleId, 'id' => $userId]);
    }

    public function findByPhone($phone) {
        if (!$this->hasPhoneColumn()) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = :phone");
        $stmt->execute(['phone' => $phone]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        return $this->insertUser($data, false);
    }

    public function createWithPasswordHash($data) {
        return $this->insertUser($data, true);
    }

    private function insertUser(array $data, bool $passwordIsHashed): bool {
        $columns = ['name', 'email', 'password', 'role', 'profile_picture'];
        
        $password = $data['password'] ?? null;
        if (empty($password) && empty($data['password_hash'])) {
            $password = bin2hex(random_bytes(16));
        }

        $params = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $passwordIsHashed ? $data['password_hash'] : password_hash($password, PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'user',
            'profile_picture' => $data['profile_picture'] ?? null,
        ];

        if (isset($data['google_id'])) {
            $columns[] = 'google_id';
            $params['google_id'] = $data['google_id'];
        }

        if ($this->hasPhoneColumn()) {
            $columns[] = 'phone';
            $params['phone'] = $data['phone'] ?? null;
        }

        if ($this->hasEmailVerifiedAtColumn()) {
            $columns[] = 'email_verified_at';
            $params['email_verified_at'] = $data['email_verified_at'] ?? null;
        }

        if ($this->hasPhoneVerifiedAtColumn()) {
            $columns[] = 'phone_verified_at';
            $params['phone_verified_at'] = $data['phone_verified_at'] ?? null;
        }

        $placeholders = array_map(static function (string $column): string {
            return ':' . $column;
        }, $columns);

        $stmt = $this->db->prepare("
            INSERT INTO users (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")
        ");

        return $stmt->execute($params);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE users SET name = :name, email = :email, role = :role";
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role']
        ];

        if ($this->hasPhoneColumn()) {
            $sql .= ", phone = :phone";
            $params['phone'] = $data['phone'] ?? null;
        }

        if ($this->hasEmailVerifiedAtColumn() && array_key_exists('email_verified_at', $data)) {
            $sql .= ", email_verified_at = :email_verified_at";
            $params['email_verified_at'] = $data['email_verified_at'];
        }

        if ($this->hasPhoneVerifiedAtColumn() && array_key_exists('phone_verified_at', $data)) {
            $sql .= ", phone_verified_at = :phone_verified_at";
            $params['phone_verified_at'] = $data['phone_verified_at'];
        }

        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log('User delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function bulkDelete($ids) {
        if (empty($ids)) {
            return true;
        }

        try {
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("DELETE FROM users WHERE id IN ($inQuery)");
            return $stmt->execute($ids);
        } catch (PDOException $e) {
            error_log('User bulk delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getPaginated($page = 1, $limit = 10, $search = '', $sortBy = 'name', $sortOrder = 'ASC') {
        $offset = ($page - 1) * $limit;
        
        // Sanitize identifiers
        $allowedSortColumns = ['id', 'name', 'email', 'role', 'created_at'];
        if ($this->hasPhoneColumn()) {
            $allowedSortColumns[] = 'phone';
        }
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'name';
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $selectColumns = [
            'id',
            'name',
            'email',
            $this->hasPhoneColumn() ? 'phone' : 'NULL AS phone',
            'role',
            $this->hasEmailVerifiedAtColumn() ? 'email_verified_at' : 'NULL AS email_verified_at',
            $this->hasPhoneVerifiedAtColumn() ? 'phone_verified_at' : 'NULL AS phone_verified_at',
            'created_at',
        ];

        $query = "SELECT " . implode(', ', $selectColumns) . " FROM users";
        $countQuery = "SELECT COUNT(*) as total FROM users";
        $params = [];

        if (!empty($search)) {
            $searchConditions = ["name LIKE :search", "email LIKE :search"];
            if ($this->hasPhoneColumn()) {
                $searchConditions[] = "phone LIKE :search";
            }
            $where = " WHERE " . implode(' OR ', $searchConditions);
            $query .= $where;
            $countQuery .= $where;
            $params['search'] = "%$search%";
        }

        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Add sorting and pagination
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
            'pages' => ceil($total / $limit)
        ];
    }

    public function getStaffAndAdmins() {
        $stmt = $this->db->query("SELECT * FROM users WHERE role IN ('admin', 'staff')");
        return $stmt->fetchAll();
    }

    public function emailExistsForOtherUser($email, $excludeId) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $stmt->execute([
            'email' => $email,
            'id' => $excludeId
        ]);
        return (bool) $stmt->fetch();
    }

    public function phoneExistsForOtherUser($phone, $excludeId) {
        if (!$this->hasPhoneColumn()) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = :phone AND id != :id LIMIT 1");
        $stmt->execute([
            'phone' => $phone,
            'id' => $excludeId
        ]);
        return (bool) $stmt->fetch();
    }

    public function getUsersWithPaymentHistory(array $ids): array {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.id, u.name
            FROM users u
            INNER JOIN payments p ON p.user_id = u.id
            WHERE u.id IN ($inQuery)
            ORDER BY u.name ASC
        ");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function updateProfile($id, array $data) {
        $allowedFields = ['name', 'email', 'profile_picture'];
        if ($this->hasPhoneColumn()) {
            $allowedFields[] = 'phone';
        }
        $sets = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $sets[] = "{$field} = :{$field}";
            $params[$field] = $data[$field];
        }

        if ($sets === []) {
            return true;
        }

        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function supportsPhoneVerificationSchema(): bool {
        return $this->hasPhoneColumn()
            && $this->hasEmailVerifiedAtColumn()
            && $this->hasPhoneVerifiedAtColumn();
    }

    public function storeResetToken($userId, $hashedToken, $expiresAt) {
        // Delete any existing token for this user first
        $this->db->prepare("DELETE FROM password_reset_tokens WHERE user_id = :user_id")
            ->execute(['user_id' => $userId]);

        $stmt = $this->db->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)"
        );
        return $stmt->execute([
            'user_id'    => $userId,
            'token'      => $hashedToken,
            'expires_at' => $expiresAt
        ]);
    }

    public function findByResetToken($hashedToken) {
        $stmt = $this->db->prepare(
            "SELECT u.*, prt.expires_at as token_expires_at
             FROM users u
             INNER JOIN password_reset_tokens prt ON prt.user_id = u.id
             WHERE prt.token = :token
             LIMIT 1"
        );
        $stmt->execute(['token' => $hashedToken]);
        return $stmt->fetch();
    }

    public function clearResetToken($userId) {
        $stmt = $this->db->prepare("DELETE FROM password_reset_tokens WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $userId]);
    }

    public function updatePassword($id, $newPassword) {
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id'       => $id
        ]);
    }

    private function hasPhoneColumn(): bool {
        if ($this->hasPhoneColumn !== null) {
            return $this->hasPhoneColumn;
        }

        $this->hasPhoneColumn = $this->columnExists('phone');
        return $this->hasPhoneColumn;
    }

    private function hasEmailVerifiedAtColumn(): bool {
        if ($this->hasEmailVerifiedAtColumn !== null) {
            return $this->hasEmailVerifiedAtColumn;
        }

        $this->hasEmailVerifiedAtColumn = $this->columnExists('email_verified_at');
        return $this->hasEmailVerifiedAtColumn;
    }

    private function hasPhoneVerifiedAtColumn(): bool {
        if ($this->hasPhoneVerifiedAtColumn !== null) {
            return $this->hasPhoneVerifiedAtColumn;
        }

        $this->hasPhoneVerifiedAtColumn = $this->columnExists('phone_verified_at');
        return $this->hasPhoneVerifiedAtColumn;
    }

    private function columnExists(string $column): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS total
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'users'
                  AND column_name = :column
            ");
            $stmt->execute(['column' => $column]);
            return (int)($stmt->fetch()['total'] ?? 0) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}

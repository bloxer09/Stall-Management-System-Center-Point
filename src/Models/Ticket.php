<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Throwable;

class Ticket {
    private $db;
    private static bool $conversationSchemaEnsured = false;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->ensureConversationSchema();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT t.*, u.name as user_name, s.stall_number
                                    FROM tickets t
                                    JOIN users u ON t.user_id = u.id
                                    JOIN stalls s ON t.stall_id = s.id
                                    WHERE t.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getPaginated($page = 1, $limit = 10, $userId = null, $search = '', $sort = 'created_at', $order = 'DESC') {
        $offset = ($page - 1) * $limit;

        $baseQuery = "FROM tickets t
                      JOIN users u ON t.user_id = u.id
                      JOIN stalls s ON t.stall_id = s.id";

        $params = [];
        $conditions = [];

        if ($userId) {
            $conditions[] = "t.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        if (!empty($search)) {
            $conditions[] = "(s.stall_number LIKE :search OR t.issue LIKE :search OR t.status LIKE :search OR u.name LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $whereClause = "";
        if (!empty($conditions)) {
            $whereClause = " WHERE " . implode(" AND ", $conditions);
        }

        $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $totalResult = $countStmt->fetch();
        $total = $totalResult ? $totalResult['total'] : 0;

        $allowedSorts = ['created_at', 'stall_number', 'status', 'issue', 'user_name'];
        $sortColumn = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';
        $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sortPrefix = 't.';
        if ($sortColumn === 'stall_number') {
            $sortPrefix = 's.';
        } elseif ($sortColumn === 'user_name') {
            $sortPrefix = 'u.';
            $sortColumn = 'name';
        }

        $query = "SELECT t.*, u.name as user_name, s.stall_number " . $baseQuery . $whereClause . " ORDER BY {$sortPrefix}{$sortColumn} {$orderDir} LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $records = $stmt->fetchAll();

        return [
            'data' => $records,
            'total' => $total,
            'page' => $page,
            'pages' => $limit > 0 ? ceil($total / $limit) : 0
        ];
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO tickets (user_id, stall_id, issue, status) VALUES (:user_id, :stall_id, :issue, 'open')");
        $created = $stmt->execute([
            'user_id' => $data['user_id'],
            'stall_id' => $data['stall_id'],
            'issue' => $data['issue']
        ]);

        if ($created) {
            $this->ensureParticipant((int)$this->db->lastInsertId(), (int)$data['user_id']);
        }

        return $created;
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE tickets SET status = :status WHERE id = :id");
        return $stmt->execute([
            'status' => $status,
            'id' => $id
        ]);
    }

    public function getTotalCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tickets");
        return $stmt->fetch()['total'];
    }

    public function getCountByStatus($status) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = :status");
        $stmt->execute(['status' => $status]);
        return $stmt->fetch()['total'];
    }

    public function getMessages($ticketId, ?int $afterId = null) {
        $sql = "
            SELECT tm.*, u.name as user_name, u.role as user_role, u.profile_picture as user_profile_picture,
                   reply.id as reply_id, reply.message as reply_message, reply.created_at as reply_created_at,
                   reply_u.name as reply_user_name, reply_u.role as reply_user_role, reply_u.profile_picture as reply_user_profile_picture
            FROM ticket_messages tm
            JOIN users u ON tm.user_id = u.id
            LEFT JOIN ticket_messages reply ON tm.reply_to_message_id = reply.id
            LEFT JOIN users reply_u ON reply.user_id = reply_u.id
            WHERE tm.ticket_id = :ticket_id
        ";

        $params = ['ticket_id' => (int)$ticketId];

        if ($afterId !== null && $afterId > 0) {
            $sql .= " AND tm.id > :after_id";
            $params['after_id'] = (int)$afterId;
        }

        $sql .= " ORDER BY tm.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findMessageById($id) {
        $stmt = $this->db->prepare("
            SELECT tm.*, t.user_id as ticket_owner_id, t.id as ticket_id
            FROM ticket_messages tm
            JOIN tickets t ON tm.ticket_id = t.id
            WHERE tm.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findMessageInTicket(int $ticketId, int $messageId): ?array {
        $stmt = $this->db->prepare("
            SELECT tm.*, u.name as user_name, u.role as user_role, u.profile_picture as user_profile_picture
            FROM ticket_messages tm
            JOIN users u ON tm.user_id = u.id
            WHERE tm.ticket_id = :ticket_id
              AND tm.id = :message_id
            LIMIT 1
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'message_id' => $messageId,
        ]);
        $message = $stmt->fetch();
        return $message ?: null;
    }

    public function addMessage($data): int {
        $replyToMessageId = null;
        if (!empty($data['reply_to_message_id'])) {
            $replyToMessageId = (int)$data['reply_to_message_id'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, message, attachment_path, reply_to_message_id)
            VALUES (:ticket_id, :user_id, :message, :attachment, :reply_to_message_id)
        ");
        $stmt->execute([
            'ticket_id' => (int)$data['ticket_id'],
            'user_id' => (int)$data['user_id'],
            'message' => $data['message'],
            'attachment' => $data['attachment_path'] ?? null,
            'reply_to_message_id' => $replyToMessageId,
        ]);

        $messageId = (int)$this->db->lastInsertId();
        $this->ensureParticipant((int)$data['ticket_id'], (int)$data['user_id']);
        $this->setTypingState((int)$data['ticket_id'], (int)$data['user_id'], false);

        return $messageId;
    }

    public function ensureParticipant(int $ticketId, int $userId): void {
        $stmt = $this->db->prepare("
            INSERT INTO ticket_participants (ticket_id, user_id, created_at, updated_at)
            VALUES (:ticket_id, :user_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
        ]);
    }

    public function markAsRead(int $ticketId, int $userId): ?int {
        $this->ensureParticipant($ticketId, $userId);

        $latestMessageId = $this->resolveValidMessageId($ticketId, $this->getLatestMessageId($ticketId));

        $currentStmt = $this->db->prepare("
            SELECT last_read_message_id
            FROM ticket_participants
            WHERE ticket_id = :ticket_id AND user_id = :user_id
            LIMIT 1
        ");
        $currentStmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
        ]);
        $currentLastRead = $currentStmt->fetchColumn();
        $currentLastRead = $currentLastRead !== false
            ? $this->resolveValidMessageId($ticketId, (int)$currentLastRead)
            : null;

        $newLastRead = $latestMessageId;
        if ($currentLastRead !== null && ($newLastRead === null || $currentLastRead > $newLastRead)) {
            $newLastRead = $currentLastRead;
        }

        $stmt = $this->db->prepare("
            UPDATE ticket_participants
            SET last_read_message_id = :last_read_message_id,
                last_read_at = CURRENT_TIMESTAMP,
                typing_at = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE ticket_id = :ticket_id AND user_id = :user_id
        ");
        $stmt->execute([
            'last_read_message_id' => $newLastRead,
            'ticket_id' => $ticketId,
            'user_id' => $userId,
        ]);

        return $newLastRead;
    }

    public function setTypingState(int $ticketId, int $userId, bool $isTyping): void {
        $this->ensureParticipant($ticketId, $userId);

        if ($isTyping) {
            $stmt = $this->db->prepare("
                UPDATE ticket_participants
                SET typing_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE ticket_id = :ticket_id AND user_id = :user_id
            ");
        } else {
            $stmt = $this->db->prepare("
                UPDATE ticket_participants
                SET typing_at = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE ticket_id = :ticket_id AND user_id = :user_id
            ");
        }

        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
        ]);
    }

    public function getConversationState(int $ticketId, int $viewerUserId, string $viewerRoleGroup): array {
        $otherRoleCondition = $viewerRoleGroup === 'staff'
            ? "u.role = 'user'"
            : "u.role IN ('admin', 'staff')";

        $seenStmt = $this->db->prepare("
            SELECT MAX(tm.id) AS other_last_read_message_id
            FROM ticket_participants tp
            JOIN users u ON u.id = tp.user_id
            LEFT JOIN ticket_messages tm
              ON tm.id = tp.last_read_message_id
             AND tm.ticket_id = tp.ticket_id
            WHERE tp.ticket_id = :ticket_id
              AND tp.user_id != :viewer_user_id
              AND {$otherRoleCondition}
        ");
        $seenStmt->execute([
            'ticket_id' => $ticketId,
            'viewer_user_id' => $viewerUserId,
        ]);
        $otherLastReadMessageId = $seenStmt->fetch()['other_last_read_message_id'] ?? null;

        $typingStmt = $this->db->prepare("
            SELECT tp.user_id, u.name as user_name, u.role as user_role
            FROM ticket_participants tp
            JOIN users u ON u.id = tp.user_id
            WHERE tp.ticket_id = :ticket_id
              AND tp.user_id != :viewer_user_id
              AND {$otherRoleCondition}
              AND tp.typing_at IS NOT NULL
              AND tp.typing_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 6 SECOND)
            ORDER BY tp.typing_at DESC
        ");
        $typingStmt->execute([
            'ticket_id' => $ticketId,
            'viewer_user_id' => $viewerUserId,
        ]);

        $typingUsers = array_map(static function (array $row): array {
            return [
                'user_id' => (int)$row['user_id'],
                'user_name' => (string)$row['user_name'],
                'user_role' => (string)$row['user_role'],
            ];
        }, $typingStmt->fetchAll());

        return [
            'other_last_read_message_id' => $otherLastReadMessageId !== null ? (int)$otherLastReadMessageId : null,
            'typing_users' => $typingUsers,
        ];
    }

    public function getLatestMessageId(int $ticketId): ?int {
        $stmt = $this->db->prepare("SELECT MAX(id) AS latest_id FROM ticket_messages WHERE ticket_id = :ticket_id");
        $stmt->execute(['ticket_id' => $ticketId]);
        $latestId = $stmt->fetch()['latest_id'] ?? null;
        return $latestId !== null ? (int)$latestId : null;
    }

    private function resolveValidMessageId(int $ticketId, ?int $messageId): ?int {
        if ($messageId === null || $messageId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT id
            FROM ticket_messages
            WHERE ticket_id = :ticket_id
              AND id = :message_id
            LIMIT 1
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'message_id' => $messageId,
        ]);

        $resolvedId = $stmt->fetchColumn();
        return $resolvedId !== false ? (int)$resolvedId : null;
    }

    public function getSlaStats() {
        $stats = [
            'overdue_open' => 0,
            'avg_first_response_minutes' => null,
        ];

        $overdueStmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM tickets
            WHERE status IN ('open', 'in_progress')
              AND created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ");
        $stats['overdue_open'] = (int)($overdueStmt->fetch()['total'] ?? 0);

        $avgStmt = $this->db->query("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, t.created_at, fr.first_response_at)) AS avg_minutes
            FROM tickets t
            JOIN (
                SELECT tm.ticket_id, MIN(tm.created_at) AS first_response_at
                FROM ticket_messages tm
                JOIN users u ON u.id = tm.user_id
                WHERE u.role IN ('admin', 'staff')
                GROUP BY tm.ticket_id
            ) fr ON fr.ticket_id = t.id
        ");
        $avg = $avgStmt->fetch()['avg_minutes'] ?? null;
        $stats['avg_first_response_minutes'] = $avg !== null ? (float)$avg : null;

        return $stats;
    }

    private function ensureConversationSchema(): void {
        if (self::$conversationSchemaEnsured) {
            return;
        }

        try {
            if (!$this->columnExists('ticket_messages', 'reply_to_message_id')) {
                $this->db->exec("ALTER TABLE ticket_messages ADD COLUMN reply_to_message_id INT NULL AFTER attachment_path");
            }

            if (!$this->indexExists('ticket_messages', 'idx_ticket_messages_reply')) {
                $this->db->exec("ALTER TABLE ticket_messages ADD INDEX idx_ticket_messages_reply (reply_to_message_id)");
            }

            if (!$this->foreignKeyExists('ticket_messages', 'reply_to_message_id', 'ticket_messages')) {
                $this->db->exec("ALTER TABLE ticket_messages ADD CONSTRAINT fk_ticket_messages_reply FOREIGN KEY (reply_to_message_id) REFERENCES ticket_messages(id) ON DELETE SET NULL");
            }

            if (!$this->tableExists('ticket_participants')) {
                $this->db->exec("
                    CREATE TABLE ticket_participants (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        ticket_id INT NOT NULL,
                        user_id INT NOT NULL,
                        last_read_message_id INT NULL,
                        last_read_at DATETIME NULL,
                        typing_at DATETIME NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uniq_ticket_participant (ticket_id, user_id),
                        INDEX idx_ticket_participants_ticket_typing (ticket_id, typing_at),
                        INDEX idx_ticket_participants_last_read (last_read_message_id),
                        CONSTRAINT fk_ticket_participants_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                        CONSTRAINT fk_ticket_participants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        CONSTRAINT fk_ticket_participants_last_read FOREIGN KEY (last_read_message_id) REFERENCES ticket_messages(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (Throwable $e) {
            error_log('Ticket conversation schema ensure failed: ' . $e->getMessage());
        }

        self::$conversationSchemaEnsured = true;
    }

    private function tableExists(string $table): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table
        ");
        $stmt->execute(['table' => $table]);
        return (int)($stmt->fetch()['total'] ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table
              AND column_name = :column
        ");
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);
        return (int)($stmt->fetch()['total'] ?? 0) > 0;
    }

    private function indexExists(string $table, string $index): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = :table
              AND index_name = :index_name
        ");
        $stmt->execute([
            'table' => $table,
            'index_name' => $index,
        ]);
        return (int)($stmt->fetch()['total'] ?? 0) > 0;
    }

    private function foreignKeyExists(string $table, string $column, string $referencedTable): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.key_column_usage
            WHERE table_schema = DATABASE()
              AND table_name = :table
              AND column_name = :column
              AND referenced_table_name = :referenced_table
        ");
        $stmt->execute([
            'table' => $table,
            'column' => $column,
            'referenced_table' => $referencedTable,
        ]);
        return (int)($stmt->fetch()['total'] ?? 0) > 0;
    }
}

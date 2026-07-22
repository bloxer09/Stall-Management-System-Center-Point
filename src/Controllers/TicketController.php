<?php

namespace App\Controllers;

use App\Config\App;
use App\Core\Controller;
use App\Models\Ticket;
use App\Models\AuditLog;
use App\Core\Auth;
use App\Core\Session;
use App\Services\FileUploadService;

class TicketController extends Controller {
    private $ticketModel;
    private $uploadService;

    public function __construct() {
        Auth::requireLogin();
        $this->ticketModel = new Ticket();
        $this->uploadService = new FileUploadService();
    }

    public function adminIndex() {
        Auth::requireStaffOrAdmin();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';
        
        $tickets = $this->ticketModel->getPaginated($page, $limit, null, $search, $sort, $order);

        return $this->view('pages/admin/tickets/index', [
            'title' => 'Manage Support Tickets',
            'tickets' => $tickets['data'],
            'total' => $tickets['total'],
            'page' => $tickets['page'],
            'pages' => $tickets['pages'],
            'search' => $search,
            'sortColumn' => $sort,
            'sortOrder' => $order
        ]);
    }

    public function adminView($id) {
        Auth::requireStaffOrAdmin();
        $context = $this->loadTicketContext((int)$id);
        if ($context === null) {
            Session::setFlash('error', 'Ticket not found.');
            $this->redirect('/admin/tickets');
            return;
        }

        $this->ticketModel->markAsRead((int)$context['ticket']['id'], (int)$context['user']['id']);
        $messages = $this->ticketModel->getMessages((int)$context['ticket']['id']);
        $conversationState = $this->ticketModel->getConversationState(
            (int)$context['ticket']['id'],
            (int)$context['user']['id'],
            $this->viewerRoleGroup($context['user'])
        );

        return $this->view('pages/admin/tickets/view', [
            'title' => 'View Ticket #' . $context['ticket']['id'],
            'ticket' => $context['ticket'],
            'messages' => $messages,
            'conversationState' => $conversationState,
            'viewerUserId' => (int)$context['user']['id'],
            'viewerRoleGroup' => $this->viewerRoleGroup($context['user']),
        ]);
    }

    public function updateStatus($id) {
        Auth::requireStaffOrAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect("/admin/tickets/view/$id");
                return;
            }

            $status = $_POST['status'] ?? 'open';
            if ($this->ticketModel->updateStatus($id, $status)) {
                try {
                    $ticket = $this->ticketModel->findById($id);
                    if ($ticket) {
                        $userObj = new \App\Models\User();
                        $tenant = $userObj->findById($ticket['user_id']);
                        if ($tenant && !empty($tenant['email'])) {
                            $mailer = new \App\Services\MailjetMailer();
                            $mailer->sendTicketStatusUpdateNotification($tenant['email'], $tenant['name'], $id, $status);
                        }
                    }
                } catch (\Throwable $ex) {
                    error_log("Failed to send ticket status update email notification: " . $ex->getMessage());
                }

                (new AuditLog())->logAction(Auth::user()['id'], 'ticket_status_updated', 'Ticket', $id, ['status' => $status]);
                Session::setFlash('success', 'Ticket status updated.');
            } else {
                Session::setFlash('error', 'Failed to update ticket.');
            }
        }
        $this->redirect("/admin/tickets/view/$id");
    }

    public function delete($id) {
        Auth::requireStaffOrAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/tickets');
                return;
            }

            $db = \App\Config\Database::getInstance();
            $fileStmt = $db->prepare("SELECT attachment_path FROM ticket_messages WHERE ticket_id = :id AND attachment_path IS NOT NULL");
            $fileStmt->execute(['id' => $id]);
            $attachments = array_column($fileStmt->fetchAll(), 'attachment_path');

            $stmt = $db->prepare("DELETE FROM tickets WHERE id = :id");
            if ($stmt->execute(['id' => $id])) {
                foreach ($attachments as $attachment) {
                    $this->deleteStoredFile($attachment);
                }
                (new AuditLog())->logAction(Auth::user()['id'], 'ticket_deleted', 'Ticket', $id);
                Session::setFlash('success', 'Ticket deleted successfully.');
            } else {
                Session::setFlash('error', 'Failed to delete ticket.');
            }
        }
        $this->redirect('/admin/tickets');
    }

    public function bulkAction() {
        Auth::requireStaffOrAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/tickets');
                return;
            }

            $action = $_POST['action'] ?? '';
            $ids = isset($_POST['ids']) ? explode(',', trim($_POST['ids'])) : [];
            $ids = array_filter(array_map('intval', $ids));

            if (empty($ids) || empty($action)) {
                Session::setFlash('error', 'Invalid bulk action request.');
                $this->redirect('/admin/tickets');
                return;
            }

            $db = \App\Config\Database::getInstance();
            $inClause = implode(',', array_fill(0, count($ids), '?'));
            $success = false;

            try {
                if ($action === 'delete') {
                    $fileStmt = $db->prepare("SELECT attachment_path FROM ticket_messages WHERE ticket_id IN ($inClause) AND attachment_path IS NOT NULL");
                    $fileStmt->execute($ids);
                    $attachments = array_column($fileStmt->fetchAll(), 'attachment_path');

                    $stmt = $db->prepare("DELETE FROM tickets WHERE id IN ($inClause)");
                    $success = $stmt->execute($ids);
                    if ($success) {
                        foreach ($attachments as $attachment) {
                            $this->deleteStoredFile($attachment);
                        }
                        (new AuditLog())->logAction(Auth::user()['id'], 'ticket_bulk_deleted', 'Ticket', null, ['ids' => $ids]);
                        Session::setFlash('success', count($ids) . ' tickets deleted successfully.');
                    }
                } elseif (in_array($action, ['open', 'in_progress', 'resolved', 'closed'])) {
                    $stmt = $db->prepare("UPDATE tickets SET status = ? WHERE id IN ($inClause)");
                    $params = array_merge([$action], $ids);
                    $success = $stmt->execute($params);
                    if ($success) {
                        (new AuditLog())->logAction(Auth::user()['id'], "ticket_bulk_status_{$action}", 'Ticket', null, ['ids' => $ids]);
                        Session::setFlash('success', count($ids) . ' tickets marked as ' . str_replace('_', ' ', $action) . '.');
                    }
                } else {
                    Session::setFlash('error', 'Unknown action.');
                }
            } catch (\PDOException $e) {
                error_log("Ticket bulk action error: " . $e->getMessage());
                Session::setFlash('error', 'Failed to perform bulk action.');
            }
        }
        $this->redirect('/admin/tickets');
    }

    // USER ROUTES

    public function userIndex() {
        $userId = Auth::user()['id'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';
        
        $tickets = $this->ticketModel->getPaginated($page, $limit, $userId, $search, $sort, $order);

        $paymentModel = new \App\Models\Payment();
        $activeMappings = $paymentModel->getActiveUserMappings($userId);

        return $this->view('pages/user/tickets/index', [
            'title' => 'My Support Tickets',
            'tickets' => $tickets['data'],
            'total' => $tickets['total'],
            'page' => $tickets['page'],
            'pages' => $tickets['pages'],
            'search' => $search,
            'sortColumn' => $sort,
            'sortOrder' => $order,
            'mappings' => $activeMappings,
        ]);
    }

    public function create() {
        $userId = Auth::user()['id'];
        
        // We use Payment model because it has the getActiveUserMappings($userId) convenience method
        $paymentModel = new \App\Models\Payment();
        $activeMappings = $paymentModel->getActiveUserMappings($userId);
        
        if (empty($activeMappings)) {
            Session::setFlash('error', 'You need an active stall assignment to create a ticket.');
            $this->redirect('/user/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/user/tickets');
                return;
            }

            $stallId = $_POST['stall_id'] ?? null;
            $issue = trim($_POST['issue'] ?? '');

            if (empty($stallId) || empty($issue)) {
                Session::setFlash('error', 'All fields are required.');
            } else {
                // Verify user has access to stall_id
                $valid = false;
                foreach ($activeMappings as $m) {
                    if ($m['stall_id'] == $stallId) {
                        $valid = true;
                        break;
                    }
                }

                if ($valid) {
                    if ($this->ticketModel->create([
                        'user_id' => $userId,
                        'stall_id' => $stallId,
                        'issue' => $issue
                     ])) {
                        $ticketId = \App\Config\Database::getInstance()->lastInsertId();
                        
                        try {
                            $userObj = new \App\Models\User();
                            $tenant = $userObj->findById($userId);
                            $stallObj = new \App\Models\Stall();
                            $stall = $stallObj->findById($stallId);
                            $stallNum = $stall ? $stall['stall_number'] : '';

                            $mailer = new \App\Services\MailjetMailer();
                            if ($tenant && !empty($tenant['email'])) {
                                $mailer->sendTicketReceiptToTenant($tenant['email'], $tenant['name'], $ticketId, $stallNum, $issue);
                            }

                            $staffUsers = $userObj->getStaffAndAdmins();
                            foreach ($staffUsers as $staff) {
                                if (!empty($staff['email'])) {
                                    $mailer->sendNewTicketNotificationToStaff($staff['email'], $staff['name'], $ticketId, $tenant['name'] ?? 'Tenant', $stallNum, $issue);
                                }
                            }
                        } catch (\Throwable $ex) {
                            error_log("Failed to send ticket creation email notification: " . $ex->getMessage());
                        }

                        (new AuditLog())->logAction($userId, 'ticket_created', 'Ticket', null, ['stall_id' => $stallId]);
                        Session::setFlash('success', 'Ticket submitted successfully.');
                        $this->redirect('/user/tickets');
                        return;
                    } else {
                        Session::setFlash('error', 'Failed to submit ticket.');
                    }
                } else {
                    Session::setFlash('error', 'You do not own the selected stall.');
                }
            }
        }

        return $this->view('pages/user/tickets/form', [
            'title' => 'Submit New Ticket',
            'mappings' => $activeMappings
        ]);
    }

    public function userView($id) {
        Auth::requireLogin();
        $context = $this->loadTicketContext((int)$id);

        if ($context === null || $context['is_staff_or_admin']) {
            Session::setFlash('error', 'Ticket not found or access denied.');
            $this->redirect('/user/tickets');
            return;
        }

        $this->ticketModel->markAsRead((int)$context['ticket']['id'], (int)$context['user']['id']);
        $messages = $this->ticketModel->getMessages((int)$context['ticket']['id']);
        $conversationState = $this->ticketModel->getConversationState(
            (int)$context['ticket']['id'],
            (int)$context['user']['id'],
            $this->viewerRoleGroup($context['user'])
        );

        return $this->view('pages/user/tickets/view', [
            'title' => 'View Ticket #' . $context['ticket']['id'],
            'ticket' => $context['ticket'],
            'messages' => $messages,
            'conversationState' => $conversationState,
            'viewerUserId' => (int)$context['user']['id'],
            'viewerRoleGroup' => $this->viewerRoleGroup($context['user']),
        ]);
    }

    public function thread($id) {
        $context = $this->loadTicketContext((int)$id);
        if ($context === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Ticket not found or access denied.'], 404);
            return;
        }

        $afterId = isset($_GET['after']) ? max(0, (int)$_GET['after']) : 0;
        $payload = $this->buildConversationPayload($context, $afterId, true);
        $this->jsonResponse(['success' => true] + $payload);
    }

    public function markRead($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $context = $this->loadTicketContext((int)$id);
        if ($context === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Ticket not found or access denied.'], 404);
            return;
        }

        $this->ticketModel->markAsRead((int)$context['ticket']['id'], (int)$context['user']['id']);
        $state = $this->ticketModel->getConversationState(
            (int)$context['ticket']['id'],
            (int)$context['user']['id'],
            $this->viewerRoleGroup($context['user'])
        );

        $this->jsonResponse([
            'success' => true,
            'conversation' => [
                'other_last_read_message_id' => $state['other_last_read_message_id'],
                'typing_users' => $state['typing_users'],
            ],
        ]);
    }

    public function typing($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $context = $this->loadTicketContext((int)$id);
        if ($context === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Ticket not found or access denied.'], 404);
            return;
        }

        $isTyping = filter_var($_POST['is_typing'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->ticketModel->setTypingState((int)$context['ticket']['id'], (int)$context['user']['id'], $isTyping);

        $this->jsonResponse(['success' => true]);
    }

    public function postMessage($id) {
        $context = $this->loadTicketContext((int)$id);
        if ($context === null) {
            if ($this->isJsonRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Ticket not found or access denied.'], 404);
                return;
            }

            Session::setFlash('error', 'Ticket not found or access denied.');
            $isStaffOrAdmin = in_array((string)(Auth::user()['role'] ?? ''), ['admin', 'staff'], true);
            $this->redirect($isStaffOrAdmin ? '/admin/tickets' : '/user/tickets');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToTicketView((int)$context['ticket']['id'], $context['is_staff_or_admin']);
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            if ($this->isJsonRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
                return;
            }

            Session::setFlash('error', 'Invalid security token.');
            $this->redirectToTicketView((int)$context['ticket']['id'], $context['is_staff_or_admin']);
            return;
        }

        $message = trim($_POST['message'] ?? '');
        $replyToMessageId = isset($_POST['reply_to_message_id']) ? (int)$_POST['reply_to_message_id'] : null;
        if ($replyToMessageId !== null && $replyToMessageId <= 0) {
            $replyToMessageId = null;
        }

        $attachmentPath = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            try {
                $upload = $this->uploadService->store($_FILES['attachment'], 'tickets', [
                    'visibility' => 'private',
                    'max_size' => 8 * 1024 * 1024,
                    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'txt'],
                    'allowed_mimes' => [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain',
                    ],
                ]);
                $attachmentPath = $upload['path'];
            } catch (\Throwable $e) {
                if ($this->isJsonRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Attachment upload failed: ' . $e->getMessage()], 422);
                    return;
                }

                Session::setFlash('error', 'Attachment upload failed: ' . $e->getMessage());
                $this->redirectToTicketView((int)$context['ticket']['id'], $context['is_staff_or_admin']);
                return;
            }
        }

        if ($message === '' && $attachmentPath === null) {
            if ($this->isJsonRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Message or attachment is required.'], 422);
                return;
            }

            Session::setFlash('error', 'Message cannot be empty.');
            $this->redirectToTicketView((int)$context['ticket']['id'], $context['is_staff_or_admin']);
            return;
        }

        if ($replyToMessageId !== null && $this->ticketModel->findMessageInTicket((int)$context['ticket']['id'], $replyToMessageId) === null) {
            if ($attachmentPath !== null) {
                $this->deleteStoredFile($attachmentPath);
            }

            if ($this->isJsonRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Reply target is no longer available.'], 422);
                return;
            }

            Session::setFlash('error', 'Reply target is no longer available.');
            $this->redirectToTicketView((int)$context['ticket']['id'], $context['is_staff_or_admin']);
            return;
        }

        try {
            $messageId = $this->ticketModel->addMessage([
                'ticket_id' => (int)$context['ticket']['id'],
                'user_id' => (int)$context['user']['id'],
                'message' => $message,
                'attachment_path' => $attachmentPath,
                'reply_to_message_id' => $replyToMessageId,
            ]);
            $this->ticketModel->markAsRead((int)$context['ticket']['id'], (int)$context['user']['id']);

            // Send email notification for the reply
            try {
                $sender = $context['user'];
                $ticketId = $context['ticket']['id'];
                $mailer = new \App\Services\MailjetMailer();
                $userObj = new \App\Models\User();
                
                $messageSnippet = strlen($message) > 200 ? substr($message, 0, 197) . '...' : $message;
                if ($message === '' && $attachmentPath !== null) {
                    $messageSnippet = "[Attachment uploaded]";
                }

                if ($sender['role'] === 'user') {
                    // Send notification to all staff/admins
                    $staffUsers = $userObj->getStaffAndAdmins();
                    foreach ($staffUsers as $staff) {
                        if (!empty($staff['email'])) {
                            $mailer->sendTicketReplyNotification($staff['email'], $staff['name'], $ticketId, $sender['name'], $messageSnippet);
                        }
                    }
                } else {
                    // Send notification to the ticket owner (tenant)
                    $ownerId = $context['ticket']['user_id'];
                    $owner = $userObj->findById($ownerId);
                    if ($owner && !empty($owner['email'])) {
                        $mailer->sendTicketReplyNotification($owner['email'], $owner['name'], $ticketId, $sender['name'], $messageSnippet);
                    }
                }
            } catch (\Throwable $ex) {
                error_log("Failed to send ticket reply email notification: " . $ex->getMessage());
            }
        } catch (\Throwable $e) {
            if ($attachmentPath !== null) {
                $this->deleteStoredFile($attachmentPath);
            }

            error_log('Ticket message post failed: ' . $e->getMessage());

            if ($this->isJsonRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to post message.'], 500);
                return;
            }

            Session::setFlash('error', 'Failed to post message.');
            $this->redirectToTicketView((int)$context['ticket']['id'], $context['is_staff_or_admin']);
            return;
        }

        if ($this->isJsonRequest()) {
            $messageRows = $this->ticketModel->getMessages((int)$context['ticket']['id'], $messageId - 1);
            $messageRow = $messageRows[0] ?? null;
            $state = $this->ticketModel->getConversationState(
                (int)$context['ticket']['id'],
                (int)$context['user']['id'],
                $this->viewerRoleGroup($context['user'])
            );

            $this->jsonResponse([
                'success' => true,
                'message' => $messageRow ? $this->serializeMessage($messageRow) : null,
                'conversation' => [
                    'other_last_read_message_id' => $state['other_last_read_message_id'],
                    'typing_users' => $state['typing_users'],
                ],
            ]);
            return;
        }

        Session::setFlash('success', 'Message posted.');
        $this->redirectToTicketView((int)$context['ticket']['id'], $context['is_staff_or_admin']);
    }

    private function buildConversationPayload(array $context, int $afterId = 0, bool $markRead = false): array {
        if ($markRead) {
            $this->ticketModel->markAsRead((int)$context['ticket']['id'], (int)$context['user']['id']);
        }

        $messages = $this->ticketModel->getMessages((int)$context['ticket']['id'], $afterId > 0 ? $afterId : null);
        $state = $this->ticketModel->getConversationState(
            (int)$context['ticket']['id'],
            (int)$context['user']['id'],
            $this->viewerRoleGroup($context['user'])
        );

        return [
            'ticket' => [
                'id' => (int)$context['ticket']['id'],
                'status' => (string)$context['ticket']['status'],
            ],
            'conversation' => [
                'viewer_user_id' => (int)$context['user']['id'],
                'viewer_role_group' => $this->viewerRoleGroup($context['user']),
                'other_last_read_message_id' => $state['other_last_read_message_id'],
                'typing_users' => $state['typing_users'],
            ],
            'messages' => array_map([$this, 'serializeMessage'], $messages),
        ];
    }

    private function serializeMessage(array $message): array {
        return [
            'id' => (int)$message['id'],
            'user_id' => (int)$message['user_id'],
            'user_name' => (string)$message['user_name'],
            'user_role' => (string)$message['user_role'],
            'user_profile_picture_url' => !empty($message['user_profile_picture'])
                ? App::path('/' . ltrim((string)$message['user_profile_picture'], '/'))
                : null,
            'message' => (string)$message['message'],
            'attachment_url' => !empty($message['attachment_path']) ? App::path('/files/ticket/' . $message['id']) : null,
            'attachment_ext' => !empty($message['attachment_path']) ? strtolower(pathinfo($message['attachment_path'], PATHINFO_EXTENSION)) : null,
            'created_at' => (string)$message['created_at'],
            'created_label' => date('M d, H:i', strtotime($message['created_at'])),
            'reply' => !empty($message['reply_id']) ? [
                'id' => (int)$message['reply_id'],
                'user_name' => (string)$message['reply_user_name'],
                'message' => (string)$message['reply_message'],
                'user_profile_picture_url' => !empty($message['reply_user_profile_picture'])
                    ? App::path('/' . ltrim((string)$message['reply_user_profile_picture'], '/'))
                    : null,
            ] : null,
        ];
    }

    private function loadTicketContext(int $ticketId): ?array {
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return null;
        }

        $user = Auth::user();
        $isStaffOrAdmin = in_array($user['role'], ['admin', 'staff'], true);
        if (!$isStaffOrAdmin && (int)$ticket['user_id'] !== (int)$user['id']) {
            return null;
        }

        return [
            'ticket' => $ticket,
            'user' => $user,
            'is_staff_or_admin' => $isStaffOrAdmin,
        ];
    }

    private function viewerRoleGroup(array $user): string {
        return in_array($user['role'], ['admin', 'staff'], true) ? 'staff' : 'user';
    }

    private function redirectToTicketView(int $ticketId, bool $isStaffOrAdmin) {
        $this->redirect($isStaffOrAdmin ? "/admin/tickets/view/{$ticketId}" : "/user/tickets/view/{$ticketId}");
    }

    private function isJsonRequest(): bool {
        $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return $requestedWith === 'xmlhttprequest' || strpos($accept, 'application/json') !== false;
    }

    private function deleteStoredFile($relativePath): void {
        $clean = ltrim((string)$relativePath, '/\\');
        if ($clean === '' || strpos($clean, '..') !== false) {
            return;
        }

        $absolute = App::projectRoot($clean);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}

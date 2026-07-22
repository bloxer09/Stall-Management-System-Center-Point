<?php

namespace App\Controllers;

use App\Config\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Mapping;
use App\Models\Payment;
use App\Services\MailjetMailer;

class PaymentController extends Controller {
    private $paymentModel;
    private $mappingModel;
    private $paymongoSecretKey;

    public function __construct() {
        if (!$this->isWebhookRequest()) {
            Auth::requireLogin();
        }

        $this->paymentModel = new Payment();
        $this->mappingModel = new Mapping();
        $this->paymongoSecretKey = (string)($_ENV['PAYMONGO_SECRET_KEY'] ?? '');
    }

    private function isWebhookRequest(): bool {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $basePath = App::basePath();
        if ($basePath !== '' && strpos($uriPath, $basePath) === 0) {
            $uriPath = substr($uriPath, strlen($basePath));
        }

        return $uriPath === '/payment/webhook';
    }

    public function adminIndex() {
        Auth::requireStaffOrAdmin();

        $this->paymentModel->generateMonthlyInvoices();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 'p.created_at';
        $sortOrder = isset($_GET['order']) ? (string)$_GET['order'] : 'DESC';

        $limit = 10;
        $payments = $this->paymentModel->getPaginated($page, $limit, $search, $sortBy, $sortOrder);

        return $this->view('pages/admin/payments/index', [
            'title' => 'Payment Records',
            'payments' => $payments['data'],
            'total' => $payments['total'],
            'page' => $payments['page'],
            'pages' => $payments['pages'],
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]);
    }

    public function userIndex() {
        $userId = (int)Auth::user()['id'];

        $this->paymentModel->generateMonthlyInvoices();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 's.stall_number';
        $sortOrder = isset($_GET['order']) ? (string)$_GET['order'] : 'ASC';
        $limit = 10;

        $mappings = $this->mappingModel->getPagedUserMappings($userId, $page, $limit, $search, $sortBy, $sortOrder);
        $paidStallIds = $this->paymentModel->getPaidStallIdsThisMonth($userId);

        return $this->view('pages/user/dashboard', [
            'title' => 'My Stalls',
            'mappings' => $mappings['data'],
            'total' => $mappings['total'],
            'page' => $mappings['page'],
            'pages' => $mappings['pages'],
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'paidStallIds' => $paidStallIds
        ]);
    }

    public function userPayments() {
        $userId = (int)Auth::user()['id'];

        $this->paymentModel->generateMonthlyInvoices();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 'p.created_at';
        $sortOrder = isset($_GET['order']) ? (string)$_GET['order'] : 'DESC';
        $limit = 10;

        $payments = $this->paymentModel->getPaginated($page, $limit, $search, $sortBy, $sortOrder, $userId);

        return $this->view('pages/user/payments', [
            'title' => 'Payment Records',
            'payments' => $payments['data'],
            'total' => $payments['total'],
            'page' => $payments['page'],
            'pages' => $payments['pages'],
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]);
    }

    public function checkout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/user/dashboard');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/user/dashboard');
            return;
        }

        $stallId = (int)($_POST['stall_id'] ?? 0);
        $userId = (int)Auth::user()['id'];

        $activeMappings = $this->paymentModel->getActiveUserMappings($userId);
        $mapping = null;
        foreach ($activeMappings as $m) {
            if ((int)$m['stall_id'] === $stallId) {
                $mapping = $m;
                break;
            }
        }

        if (!$mapping) {
            Session::setFlash('error', 'Invalid stall mapping.');
            $this->redirect('/user/dashboard');
            return;
        }

        $invoice = $this->paymentModel->getPendingInvoiceForCheckout($userId, $stallId);
        if (!$invoice) {
            $invoiceId = $this->paymentModel->getOrCreateMonthlyInvoice(
                $userId,
                $stallId,
                (float)$mapping['price'],
                (string)$mapping['start_date']
            );
            $invoice = $this->paymentModel->findById($invoiceId);
        }

        if (!$invoice) {
            Session::setFlash('error', 'Unable to prepare an invoice for checkout.');
            $this->redirect('/user/dashboard');
            return;
        }

        if ($invoice['status'] === 'paid') {
            Session::setFlash('success', 'This invoice is already paid.');
            $this->redirect('/user/dashboard');
            return;
        }

        $paymentId = (int)$invoice['id'];
        $amount = (float)$invoice['amount'];

        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'line_items' => [
                        [
                            'currency' => 'PHP',
                            'amount' => (int)round($amount * 100),
                            'description' => 'Payment for Stall ' . $mapping['stall_number'],
                            'name' => 'Stall Rent Invoice #' . $paymentId,
                            'quantity' => 1,
                        ]
                    ],
                    'payment_method_types' => ['gcash', 'paymaya', 'card'],
                    'success_url' => App::url('/payment/success?id=' . $paymentId),
                    'cancel_url' => App::url('/payment/cancel?id=' . $paymentId),
                    'description' => 'Invoice #' . $paymentId,
                    'reference_number' => 'stallm_payment_' . $paymentId,
                ]
            ]
        ];

        $api = $this->paymongoRequest('POST', '/checkout_sessions', $payload);

        if (!$api['ok']) {
            Session::setFlash('error', 'Failed to initialize payment gateway: ' . $api['error']);
            $this->redirect('/user/dashboard');
            return;
        }

        $result = $api['data'];
        $checkoutUrl = $result['data']['attributes']['checkout_url'] ?? null;
        $checkoutSessionId = $result['data']['id'] ?? null;

        if ($checkoutUrl && $checkoutSessionId) {
            $this->paymentModel->updateStatus($paymentId, 'pending', $checkoutSessionId);
            header('Location: ' . $checkoutUrl);
            exit();
        }

        Session::setFlash('error', 'Payment gateway did not return a valid checkout session.');
        $this->redirect('/user/dashboard');
    }

    public function paymongoSuccess() {
        $paymentId = (int)($_GET['id'] ?? 0);
        if ($paymentId <= 0) {
            Session::setFlash('error', 'Invalid payment request.');
            $this->redirect('/user/dashboard');
            return;
        }

        $payment = $this->paymentModel->findById($paymentId);
        $user = Auth::user();
        $isPrivileged = in_array($user['role'], ['admin', 'staff'], true);

        if (!$payment || (!$isPrivileged && (int)$payment['user_id'] !== (int)$user['id'])) {
            Session::setFlash('error', 'Payment not found or access denied.');
            $this->redirect('/user/dashboard');
            return;
        }

        if ($payment['status'] === 'paid') {
            Session::setFlash('success', 'Payment already confirmed.');
            $this->redirect('/user/dashboard');
            return;
        }

        if (empty($payment['paymongo_id'])) {
            Session::setFlash('error', 'Missing checkout session reference.');
            $this->redirect('/user/dashboard');
            return;
        }

        $verification = $this->verifyCheckoutSession($payment['paymongo_id']);

        if (!$verification['ok']) {
            Session::setFlash('error', 'Unable to verify payment yet. Please refresh in a moment.');
            $this->redirect('/user/dashboard');
            return;
        }

        if ($verification['status'] === 'paid') {
            $this->paymentModel->updateStatus($paymentId, 'paid', $payment['paymongo_id']);
            
            $batch = $_GET['batch'] ?? '';
            if (!empty($batch)) {
                $batchPids = explode('_', $batch);
                foreach ($batchPids as $bpid) {
                    $bpidInt = (int)$bpid;
                    if ($bpidInt > 0) {
                        $this->paymentModel->updateStatus($bpidInt, 'paid', $payment['paymongo_id']);
                    }
                }
            }

            Session::setFlash('success', 'Payment successful and verified.');
            (new AuditLog())->logAction((int)$user['id'], 'payment_verified_paid', 'Payment', $paymentId, ['paymongo_id' => $payment['paymongo_id']]);
        } elseif (in_array($verification['status'], ['failed', 'expired', 'canceled'], true)) {
            $this->paymentModel->updateStatus($paymentId, 'failed', $payment['paymongo_id']);
            Session::setFlash('error', 'Payment did not complete successfully.');
        } else {
            Session::setFlash('warning', 'Payment is still being processed.');
        }

        $this->redirect('/user/dashboard');
    }

    public function multiCheckout() {
        Auth::requireUser();
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/user/payments');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/user/payments');
            return;
        }

        $paymentIds = $_POST['payment_ids'] ?? [];
        if (empty($paymentIds) || !is_array($paymentIds)) {
            Session::setFlash('error', 'Please select at least one pending payment to check out.');
            $this->redirect('/user/payments');
            return;
        }

        $lineItems = [];
        $validPaymentIds = [];
        $totalAmount = 0;

        foreach ($paymentIds as $pid) {
            $paymentId = (int)$pid;
            $invoice = $this->paymentModel->findById($paymentId);
            if ($invoice && (int)$invoice['user_id'] === (int)$user['id'] && $invoice['status'] === 'pending') {
                $amount = (float)$invoice['amount'];
                $totalAmount += $amount;
                $validPaymentIds[] = $paymentId;
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int)round($amount * 100),
                    'description' => 'Rental payment due ' . ($invoice['due_date'] ?? 'current period'),
                    'name' => 'Invoice #' . $paymentId . ' (Stall ' . ($invoice['stall_number'] ?? '') . ')',
                    'quantity' => 1,
                ];
            }
        }

        if (empty($validPaymentIds)) {
            Session::setFlash('error', 'No valid pending payments were selected.');
            $this->redirect('/user/payments');
            return;
        }

        $batchId = implode('_', $validPaymentIds);
        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'line_items' => $lineItems,
                    'payment_method_types' => ['gcash', 'paymaya', 'card'],
                    'success_url' => App::url('/payment/success?id=' . $validPaymentIds[0] . '&batch=' . $batchId),
                    'cancel_url' => App::url('/payment/cancel?id=' . $validPaymentIds[0]),
                    'description' => 'Aggregate Payment for Invoices: ' . implode(', ', $validPaymentIds),
                    'reference_number' => 'stallm_batch_' . substr(md5($batchId), 0, 10),
                ]
            ]
        ];

        $api = $this->paymongoRequest('POST', '/checkout_sessions', $payload);
        if (!$api['ok']) {
            Session::setFlash('error', 'Failed to initialize aggregate checkout: ' . $api['error']);
            $this->redirect('/user/payments');
            return;
        }

        $result = $api['data'];
        $checkoutUrl = $result['data']['attributes']['checkout_url'] ?? null;
        $checkoutSessionId = $result['data']['id'] ?? null;

        if ($checkoutUrl && $checkoutSessionId) {
            foreach ($validPaymentIds as $pid) {
                $this->paymentModel->updateStatus($pid, 'pending', $checkoutSessionId);
            }
            header('Location: ' . $checkoutUrl);
            exit();
        }

        Session::setFlash('error', 'Payment gateway did not return a valid checkout URL.');
        $this->redirect('/user/payments');
    }

    public function paymongoCancel() {
        $paymentId = (int)($_GET['id'] ?? 0);
        if ($paymentId > 0) {
            $payment = $this->paymentModel->findById($paymentId);
            if ($payment && $payment['status'] === 'pending') {
                $this->paymentModel->updateStatus($paymentId, 'failed', $payment['paymongo_id'] ?? null);
            }
        }

        Session::setFlash('error', 'Payment was canceled.');
        $this->redirect('/user/dashboard');
    }

    public function paymongoWebhook() {
        header('Content-Type: application/json');

        $token = (string)($_ENV['PAYMONGO_WEBHOOK_TOKEN'] ?? '');
        if ($token !== '') {
            $provided = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? ($_GET['token'] ?? '');
            if (!hash_equals($token, (string)$provided)) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
                return;
            }
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Invalid JSON payload']);
            return;
        }

        $eventType = strtolower((string)($payload['data']['attributes']['type'] ?? ''));
        $sessionId = (string)($payload['data']['attributes']['data']['id'] ?? '');

        if ($sessionId === '') {
            http_response_code(202);
            echo json_encode(['ok' => true, 'message' => 'No checkout session id in payload']);
            return;
        }

        $payment = $this->paymentModel->findByPaymongoId($sessionId);
        if (!$payment) {
            http_response_code(202);
            echo json_encode(['ok' => true, 'message' => 'Payment mapping not found']);
            return;
        }

        if (str_contains($eventType, 'paid')) {
            $this->paymentModel->updateStatus((int)$payment['id'], 'paid', $sessionId);
        } elseif (str_contains($eventType, 'failed') || str_contains($eventType, 'expired') || str_contains($eventType, 'cancel')) {
            $this->paymentModel->updateStatus((int)$payment['id'], 'failed', $sessionId);
        }

        http_response_code(200);
        echo json_encode(['ok' => true]);
    }

    private function verifyCheckoutSession($sessionId): array {
        $api = $this->paymongoRequest('GET', '/checkout_sessions/' . urlencode($sessionId));
        if (!$api['ok']) {
            return ['ok' => false, 'status' => 'unknown'];
        }

        $attributes = $api['data']['data']['attributes'] ?? [];
        $status = strtolower((string)($attributes['payment_status'] ?? $attributes['status'] ?? ''));

        if ($status === '' && !empty($attributes['payments']) && is_array($attributes['payments'])) {
            $firstPayment = $attributes['payments'][0]['attributes']['status'] ?? '';
            $status = strtolower((string)$firstPayment);
        }

        if ($status === '' && isset($attributes['payment_intent']['attributes']['status'])) {
            $status = strtolower((string)$attributes['payment_intent']['attributes']['status']);
        }

        if ($status === 'succeeded') {
            $status = 'paid';
        }

        return ['ok' => true, 'status' => $status !== '' ? $status : 'pending'];
    }

    private function paymongoRequest(string $method, string $endpoint, ?array $payload = null): array {
        if ($this->paymongoSecretKey === '') {
            return ['ok' => false, 'error' => 'PayMongo secret key is not configured.', 'data' => null];
        }

        $ch = curl_init();
        $url = 'https://api.paymongo.com/v1' . $endpoint;

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->paymongoSecretKey . ':'),
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError) {
            return ['ok' => false, 'error' => $curlError ?: 'Unknown cURL error.', 'data' => null];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Invalid API response.', 'data' => null];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $decoded['errors'][0]['detail'] ?? $decoded['errors'][0]['title'] ?? ('PayMongo request failed with HTTP ' . $httpCode);
            return ['ok' => false, 'error' => $message, 'data' => $decoded];
        }

        return ['ok' => true, 'error' => null, 'data' => $decoded];
    }

    public function generateMonthlyInvoices() {
        Auth::requireStaffOrAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/payments');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/payments');
            return;
        }

        $created = $this->paymentModel->generateMonthlyInvoices();
        (new AuditLog())->logAction(Auth::user()['id'], 'monthly_invoices_generated', 'Payment', null, ['created' => $created]);
        Session::setFlash('success', "Monthly invoice generation complete. {$created} new invoice(s) created.");
        $this->redirect('/admin/payments');
    }

    public function sendDueReminders() {
        Auth::requireStaffOrAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/payments');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/payments');
            return;
        }

        $duePayments = $this->paymentModel->getDueReminders(7);
        if (empty($duePayments)) {
            Session::setFlash('success', 'No due reminders to send right now.');
            $this->redirect('/admin/payments');
            return;
        }

        $mailer = new MailjetMailer();
        $smsService = new \App\Services\SmsService();
        $sentCount = 0;
        $smsSentCount = 0;

        foreach ($duePayments as $payment) {
            $dueDate = $payment['due_date'] ? date('F j, Y', strtotime($payment['due_date'])) : null;
            $ok = $mailer->sendPaymentReminderEmail(
                (string)$payment['user_email'],
                (string)$payment['user_name'],
                (string)$payment['stall_number'],
                (float)$payment['amount'],
                $dueDate
            );

            if ($ok) {
                $sentCount++;
            }

            if (!empty($payment['user_phone'])) {
                $dueDateStr = $dueDate ?: 'the end of the month';
                $smsMessage = sprintf(
                    "Hello %s, this is a reminder that your payment of PHP %s for Stall %s is due on %s. Please log in to your STALLM dashboard to settle it. Thank you!",
                    $payment['user_name'],
                    number_format((float)$payment['amount'], 2),
                    $payment['stall_number'],
                    $dueDateStr
                );
                $smsResult = $smsService->send((string)$payment['user_phone'], $smsMessage);
                if ($smsResult['sent']) {
                    $smsSentCount++;
                }
            }
        }

        (new AuditLog())->logAction(Auth::user()['id'], 'payment_due_reminders_sent', 'Payment', null, [
            'total_due' => count($duePayments),
            'emails_sent' => $sentCount,
            'sms_sent' => $smsSentCount,
        ]);

        Session::setFlash('success', "Reminder emails sent: {$sentCount}/" . count($duePayments) . ". SMS sent: {$smsSentCount}/" . count($duePayments));
        $this->redirect('/admin/payments');
    }

    public function recordOfficePayment() {
        Auth::requireStaffOrAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/payments');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/payments');
            return;
        }

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $reference = trim((string)($_POST['payment_reference'] ?? ''));
        $notes = trim((string)($_POST['payment_notes'] ?? ''));
        $paidAtInput = trim((string)($_POST['paid_at'] ?? ''));

        if ($paymentId <= 0) {
            Session::setFlash('error', 'Invalid payment record.');
            $this->redirect('/admin/payments');
            return;
        }

        $payment = $this->paymentModel->findById($paymentId);
        if (!$payment) {
            Session::setFlash('error', 'Payment record not found.');
            $this->redirect('/admin/payments');
            return;
        }

        if ((string)$payment['status'] === 'paid') {
            Session::setFlash('info', 'This invoice is already marked as paid.');
            $this->redirect('/admin/payments');
            return;
        }

        if ($paidAtInput === '') {
            $paidAtInput = date('Y-m-d\TH:i');
        }

        $timestamp = strtotime($paidAtInput);
        if ($timestamp === false) {
            Session::setFlash('error', 'Enter a valid office payment date and time.');
            $this->redirect('/admin/payments');
            return;
        }

        $paidAt = date('Y-m-d H:i:s', $timestamp);

        $recorded = $this->paymentModel->recordOfficePayment($paymentId, $paidAt, $reference, $notes);
        if (!$recorded) {
            Session::setFlash('error', 'Unable to record the office payment.');
            $this->redirect('/admin/payments');
            return;
        }

        (new AuditLog())->logAction(Auth::user()['id'], 'payment_recorded_office', 'Payment', $paymentId, [
            'payment_method' => 'office',
            'payment_reference' => $reference !== '' ? $reference : null,
            'paid_at' => $paidAt,
            'notes' => $notes !== '' ? $notes : null,
        ]);

        Session::setFlash('success', 'Office payment recorded successfully.');
        $this->redirect('/admin/payments');
    }

    public function downloadReceipt($id) {
        $userId = (int)Auth::user()['id'];
        $payment = $this->paymentModel->findById($id);

        if (!$payment || ((int)$payment['user_id'] !== $userId && !Auth::isAdmin() && !Auth::isStaff())) {
            Session::setFlash('error', 'Receipt not found or access denied.');
            $this->redirect('/user/dashboard');
            return;
        }

        if ($payment['status'] !== 'paid') {
            Session::setFlash('error', 'Receipt is only available for paid transactions.');
            $this->redirect('/user/dashboard');
            return;
        }

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new \Dompdf\Dompdf($options);

        $dueDateText = !empty($payment['due_date']) ? date('F j, Y', strtotime($payment['due_date'])) : 'N/A';

        $transactionAt = !empty($payment['paid_at']) ? $payment['paid_at'] : $payment['created_at'];
        $referenceText = (string)($payment['payment_reference'] ?: $payment['paymongo_id'] ?: 'N/A');
        $paymentMethod = (string)($payment['payment_method'] ?: (!empty($payment['paymongo_id']) ? 'online' : 'N/A'));
        $paymentMethodLabel = $paymentMethod === 'N/A' ? 'N/A' : ucfirst($paymentMethod);

        $verifyUrl = App::url('/payment/verify-receipt/' . $payment['id']);
        $qrHtml = \App\Services\QrCodeGenerator::renderSvgQrCode($verifyUrl, 120);

        $html = '
        <html>
        <head>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; color: #333; }
                .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
                .title { font-size: 24px; color: #2563eb; margin: 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details th { text-align: left; padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td { text-align: right; padding: 8px 0; border-bottom: 1px solid #eee; }
                .total { font-size: 18px; font-weight: bold; }
                .qr-container { text-align: center; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="title">' . htmlspecialchars(App::name(), ENT_QUOTES, 'UTF-8') . '</h1>
                <p>Official Receipt</p>
            </div>
            <div class="details">
                <table>
                    <tr><th>Receipt No.</th><td>#' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT) . '</td></tr>
                    <tr><th>Transaction Date</th><td>' . date('F j, Y, g:i a', strtotime($transactionAt)) . '</td></tr>
                    <tr><th>Due Date</th><td>' . htmlspecialchars($dueDateText) . '</td></tr>
                    <tr><th>Tenant Name</th><td>' . htmlspecialchars($payment['user_name']) . '</td></tr>
                    <tr><th>Stall Number</th><td>Stall ' . htmlspecialchars($payment['stall_number']) . '</td></tr>
                    <tr><th>Payment Method</th><td>' . htmlspecialchars($paymentMethodLabel) . '</td></tr>
                    <tr><th>Reference ID</th><td>' . htmlspecialchars($referenceText) . '</td></tr>
                    <tr><th class="total">Total Amount Paid</th><td class="total">PHP ' . number_format((float)$payment['amount'], 2) . '</td></tr>
                </table>
            </div>
            <div class="qr-container">
                ' . $qrHtml . '
                <p style="font-size: 10px; color: #777; margin-top: 5px;">Scan QR Code to verify authenticity online</p>
            </div>
            <div style="text-align: center; color: #666; font-size: 12px; margin-top: 30px;">
                <p>This is a computer-generated document. No signature is required.</p>
                <p>Thank you for your payment!</p>
            </div>
        </body>
        </html>
        ';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'receipt_' . date('Ymd', strtotime($payment['created_at'])) . '_stall_' . $payment['stall_number'] . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit();
    }

    public function verifyReceipt($id) {
        $paymentId = (int)$id;
        $payment = $this->paymentModel->findById($paymentId);

        return $this->view('pages/verify_receipt', [
            'title' => App::pageTitle('Receipt Verification'),
            'payment' => $payment
        ]);
    }

    public function exportCsv() {
        Auth::requireStaffOrAdmin();

        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 'p.created_at';
        $sortOrder = isset($_GET['order']) ? (string)$_GET['order'] : 'DESC';

        $payments = $this->paymentModel->getPaginated(1, 100000, $search, $sortBy, $sortOrder)['data'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=payments_export_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Tenant Name', 'Stall Number', 'Location', 'Amount', 'Status', 'Method', 'Reference', 'Due Date', 'Paid At', 'Gateway ID', 'Date']);

        foreach ($payments as $payment) {
            fputcsv($output, [
                $payment['id'],
                $payment['user_name'],
                $payment['stall_number'],
                $payment['location_name'] ?? '',
                $payment['amount'],
                $payment['status'],
                $payment['payment_method'],
                $payment['payment_reference'],
                $payment['due_date'],
                $payment['paid_at'],
                $payment['paymongo_id'],
                $payment['created_at'],
            ]);
        }

        fclose($output);
        exit();
    }
}

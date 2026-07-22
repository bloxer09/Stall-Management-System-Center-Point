<?php

if (php_sapi_name() !== 'cli' && !isset($_GET['secret_token'])) {
    http_response_code(403);
    echo "Access denied: CLI execution only.\n";
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

echo "[" . date('Y-m-d H:i:s') . "] Starting STALLM Automated Invoicing & Reminder Cron Job...\n";

// 1. Generate Monthly Invoices
$paymentModel = new \App\Models\Payment();
$generatedCount = $paymentModel->generateMonthlyInvoices();
echo "[" . date('Y-m-d H:i:s') . "] Monthly Invoicing Completed. New invoices created: {$generatedCount}\n";

// 2. Scan and Dispatch Due Reminders
$upcomingDues = $paymentModel->getUpcomingDues();
$notifModel = new \App\Models\Notification();
$mailer = new \App\Services\MailjetMailer();

$remindersSent = 0;
foreach ($upcomingDues as $due) {
    $daysLeft = (int) ceil((strtotime($due['due_date']) - time()) / 86400);
    
    if (in_array($daysLeft, [0, 3, 7], true)) {
        $title = $daysLeft === 0 ? "Rent Due Today - Stall {$due['stall_number']}" : "Rent Due in {$daysLeft} Day(s) - Stall {$due['stall_number']}";
        $message = "Your monthly rental payment of ₱" . number_format($due['amount'], 2) . " for Stall {$due['stall_number']} is due on {$due['due_date']}.";

        $notifModel->create([
            'user_id' => $due['user_id'],
            'type'    => $daysLeft === 0 ? 'danger' : 'warning',
            'title'   => $title,
            'message' => $message,
            'link'    => '/user/payments'
        ]);

        if (!empty($due['email'])) {
            $mailer->sendPaymentReminderEmail($due['email'], $due['tenant_name'], $due['stall_number'], $due['amount'], $due['due_date']);
        }
        $remindersSent++;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Reminders Processed. Dispatched reminders: {$remindersSent}\n";
echo "[" . date('Y-m-d H:i:s') . "] Cron Job Finished Successfully.\n";

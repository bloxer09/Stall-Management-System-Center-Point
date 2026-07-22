<?php

// Require Composer Autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// Load Environment Variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Basic Custom Autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../src/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Register Global Error Handler (for uncaught exceptions and fatal errors)
\App\Core\ErrorHandler::register();

// Start Session
\App\Core\Session::start();

// Initialize Router
$router = new \App\Core\Router();

// Define Routes
// Home
$router->get('/', [\App\Controllers\HomeController::class, 'index']);

// Auth
$router->get('/login', [\App\Controllers\AuthController::class, 'showLoginForm']);
$router->post('/login', [\App\Controllers\AuthController::class, 'processLogin']);
$router->get('/register', [\App\Controllers\AuthController::class, 'showRegisterForm']);
$router->post('/register', [\App\Controllers\AuthController::class, 'processRegister']);
$router->get('/logout', [\App\Controllers\AuthController::class, 'logout']);

// OAuth Social Logins
$router->get('/auth/google', [\App\Controllers\AuthController::class, 'redirectToGoogle']);
$router->get('/auth/google/callback', [\App\Controllers\AuthController::class, 'handleGoogleCallback']);

// Login verification and OAuth compliance routes
$router->get('/login/verify', [\App\Controllers\AuthController::class, 'showLoginVerifyForm']);
$router->post('/login/verify', [\App\Controllers\AuthController::class, 'processLoginVerify']);
$router->post('/login/verify/resend', [\App\Controllers\AuthController::class, 'resendLoginOtp']);
$router->get('/auth/confirm-terms', [\App\Controllers\AuthController::class, 'showOauthTermsForm']);
$router->post('/auth/confirm-terms', [\App\Controllers\AuthController::class, 'processOauthTerms']);

// Password Reset (unauthenticated)
$router->get('/forgot-password', [\App\Controllers\AuthController::class, 'showForgotPasswordForm']);
$router->post('/forgot-password', [\App\Controllers\AuthController::class, 'processForgotPassword']);
$router->get('/reset-password', [\App\Controllers\AuthController::class, 'showResetPasswordForm']);
$router->post('/reset-password', [\App\Controllers\AuthController::class, 'processResetPassword']);

// Change Password (authenticated user)
$router->get('/user/change-password', [\App\Controllers\AuthController::class, 'showChangePasswordForm']);
$router->post('/user/change-password', [\App\Controllers\AuthController::class, 'processChangePassword']);
$router->get('/user/change-password/verify', [\App\Controllers\AuthController::class, 'showChangePasswordVerifyForm']);
$router->post('/user/change-password/verify', [\App\Controllers\AuthController::class, 'processChangePasswordVerify']);
$router->get('/admin/change-password', [\App\Controllers\AuthController::class, 'showAdminChangePasswordForm']);
$router->post('/admin/change-password', [\App\Controllers\AuthController::class, 'processAdminChangePassword']);
$router->get('/admin/change-password/verify', [\App\Controllers\AuthController::class, 'showAdminChangePasswordVerifyForm']);
$router->post('/admin/change-password/verify', [\App\Controllers\AuthController::class, 'processAdminChangePasswordVerify']);

// Admin Routes
$router->get('/admin/dashboard', [\App\Controllers\AdminController::class, 'dashboard']);

// Admin - Stalls
$router->get('/admin/stalls', [\App\Controllers\StallController::class, 'index']);
$router->get('/admin/stalls/map', [\App\Controllers\StallMapController::class, 'adminMap']);
$router->post('/admin/stalls/update-layout', [\App\Controllers\StallMapController::class, 'updateLayout']);
$router->get('/admin/stalls/export', [\App\Controllers\StallController::class, 'exportCsv']);
$router->get('/admin/stalls/create', [\App\Controllers\StallController::class, 'create']);
$router->post('/admin/stalls/create', [\App\Controllers\StallController::class, 'create']);
$router->get('/admin/stalls/edit/{id}', [\App\Controllers\StallController::class, 'edit']);
$router->post('/admin/stalls/edit/{id}', [\App\Controllers\StallController::class, 'edit']);
$router->post('/admin/stalls/delete/{id}', [\App\Controllers\StallController::class, 'delete']);
$router->post('/admin/stalls/bulk', [\App\Controllers\StallController::class, 'bulkAction']);

// Admin - Stall Locations
$router->get('/admin/stall-locations', [\App\Controllers\StallLocationController::class, 'index']);
$router->get('/admin/stall-locations/create', [\App\Controllers\StallLocationController::class, 'create']);
$router->post('/admin/stall-locations/create', [\App\Controllers\StallLocationController::class, 'create']);
$router->get('/admin/stall-locations/edit/{id}', [\App\Controllers\StallLocationController::class, 'edit']);
$router->post('/admin/stall-locations/edit/{id}', [\App\Controllers\StallLocationController::class, 'edit']);
$router->post('/admin/stall-locations/delete/{id}', [\App\Controllers\StallLocationController::class, 'delete']);

// Admin - Users
$router->get('/admin/users', [\App\Controllers\UserController::class, 'index']);
$router->get('/admin/users/export', [\App\Controllers\UserController::class, 'exportCsv']);
$router->get('/admin/users/create', [\App\Controllers\UserController::class, 'create']);
$router->post('/admin/users/create', [\App\Controllers\UserController::class, 'create']);
$router->get('/admin/users/edit/{id}', [\App\Controllers\UserController::class, 'edit']);
$router->post('/admin/users/edit/{id}', [\App\Controllers\UserController::class, 'edit']);
$router->post('/admin/users/delete/{id}', [\App\Controllers\UserController::class, 'delete']);
$router->post('/admin/users/bulk', [\App\Controllers\UserController::class, 'bulkAction']);

// Admin - Analytics
$router->get('/admin/analytics', [\App\Controllers\AnalyticsController::class, 'index']);

// Admin - Mappings
$router->get('/admin/mappings', [\App\Controllers\MappingController::class, 'index']);
$router->get('/admin/mappings/create', [\App\Controllers\MappingController::class, 'create']);
$router->post('/admin/mappings/create', [\App\Controllers\MappingController::class, 'create']);
$router->get('/admin/mappings/edit/{id}', [\App\Controllers\MappingController::class, 'edit']);
$router->post('/admin/mappings/edit/{id}', [\App\Controllers\MappingController::class, 'edit']);
$router->post('/admin/mappings/delete/{id}', [\App\Controllers\MappingController::class, 'delete']);
$router->post('/admin/mappings/renew/{id}', [\App\Controllers\MappingController::class, 'renew']);
$router->post('/admin/mappings/approve-renewal/{id}', [\App\Controllers\MappingController::class, 'approveRenewal']);
$router->post('/admin/mappings/reject-renewal/{id}', [\App\Controllers\MappingController::class, 'rejectRenewal']);
$router->post('/user/mappings/request-renewal/{id}', [\App\Controllers\MappingController::class, 'requestRenewal']);

// Admin - Payments
$router->get('/admin/payments', [\App\Controllers\PaymentController::class, 'adminIndex']);
$router->get('/admin/payments/export', [\App\Controllers\PaymentController::class, 'exportCsv']);
$router->post('/admin/payments/generate-monthly', [\App\Controllers\PaymentController::class, 'generateMonthlyInvoices']);
$router->post('/admin/payments/send-reminders', [\App\Controllers\PaymentController::class, 'sendDueReminders']);
$router->post('/admin/payments/record-office', [\App\Controllers\PaymentController::class, 'recordOfficePayment']);

// Admin - Tickets
$router->get('/admin/tickets', [\App\Controllers\TicketController::class, 'adminIndex']);
$router->get('/admin/tickets/view/{id}', [\App\Controllers\TicketController::class, 'adminView']);
$router->post('/admin/tickets/update/{id}', [\App\Controllers\TicketController::class, 'updateStatus']);
$router->post('/admin/tickets/delete/{id}', [\App\Controllers\TicketController::class, 'delete']);
$router->post('/admin/tickets/bulk', [\App\Controllers\TicketController::class, 'bulkAction']);
// Generic Ticket Routes
$router->get('/tickets/thread/{id}', [\App\Controllers\TicketController::class, 'thread']);
$router->post('/tickets/message/{id}', [\App\Controllers\TicketController::class, 'postMessage']);
$router->post('/tickets/read/{id}', [\App\Controllers\TicketController::class, 'markRead']);
$router->post('/tickets/typing/{id}', [\App\Controllers\TicketController::class, 'typing']);

// Admin - Audit Logs
$router->get('/admin/audit-logs', [\App\Controllers\AuditLogController::class, 'index']);
$router->get('/admin/audit-logs/export', [\App\Controllers\AuditLogController::class, 'exportCsv']);

// Admin - Error Logs
$router->get('/admin/error-logs', [\App\Controllers\ErrorLogController::class, 'index']);
$router->post('/admin/error-logs/delete', [\App\Controllers\ErrorLogController::class, 'delete']);
$router->post('/admin/error-logs/clear', [\App\Controllers\ErrorLogController::class, 'clear']);

// Admin - Settings
$router->get('/admin/settings/privacy', [\App\Controllers\SettingController::class, 'privacyForm']);
$router->post('/admin/settings/privacy', [\App\Controllers\SettingController::class, 'updatePrivacy']);
$router->get('/admin/settings/terms', [\App\Controllers\SettingController::class, 'termsForm']);
$router->post('/admin/settings/terms', [\App\Controllers\SettingController::class, 'updateTerms']);
$router->get('/admin/settings/backups', [\App\Controllers\BackupController::class, 'index']);
$router->post('/admin/settings/backups/create', [\App\Controllers\BackupController::class, 'create']);
$router->get('/admin/settings/backups/download/{filename}', [\App\Controllers\BackupController::class, 'download']);
$router->post('/admin/settings/backups/delete/{filename}', [\App\Controllers\BackupController::class, 'delete']);

// Notifications
$router->get('/notifications', [\App\Controllers\NotificationController::class, 'index']);
$router->get('/notifications/unread-count', [\App\Controllers\NotificationController::class, 'getUnreadCount']);
$router->post('/notifications/mark-read/{id}', [\App\Controllers\NotificationController::class, 'markRead']);
$router->post('/notifications/read-all', [\App\Controllers\NotificationController::class, 'markAllRead']);

// Public - Policies
$router->get('/privacy', [\App\Controllers\SettingController::class, 'showPrivacyPolicy']);
$router->get('/terms', [\App\Controllers\SettingController::class, 'showTermsConditions']);

// User / Tenant Routes
$router->get('/user/dashboard', [\App\Controllers\PaymentController::class, 'userIndex']);
$router->get('/user/stalls/map', [\App\Controllers\StallMapController::class, 'userMap']);
$router->get('/user/payments', [\App\Controllers\PaymentController::class, 'userPayments']);
$router->get('/user/profile', [\App\Controllers\ProfileController::class, 'index']);
$router->post('/user/profile', [\App\Controllers\ProfileController::class, 'update']);
$router->get('/admin/profile', [\App\Controllers\ProfileController::class, 'index']);
$router->post('/admin/profile', [\App\Controllers\ProfileController::class, 'update']);
$router->get('/user/tickets', [\App\Controllers\TicketController::class, 'userIndex']);
$router->get('/user/tickets/create', [\App\Controllers\TicketController::class, 'create']);
$router->post('/user/tickets/create', [\App\Controllers\TicketController::class, 'create']);
$router->get('/user/tickets/view/{id}', [\App\Controllers\TicketController::class, 'userView']);
$router->post('/payment/checkout', [\App\Controllers\PaymentController::class, 'checkout']);
$router->post('/payment/multi-checkout', [\App\Controllers\PaymentController::class, 'multiCheckout']);
$router->get('/payment/success', [\App\Controllers\PaymentController::class, 'paymongoSuccess']);
$router->get('/payment/cancel', [\App\Controllers\PaymentController::class, 'paymongoCancel']);
$router->post('/payment/webhook', [\App\Controllers\PaymentController::class, 'paymongoWebhook']);
$router->get('/payment/receipt/{id}', [\App\Controllers\PaymentController::class, 'downloadReceipt']);
$router->get('/payment/verify-receipt/{id}', [\App\Controllers\PaymentController::class, 'verifyReceipt']);

// Admin - Utilities
$router->get('/admin/utilities', [\App\Controllers\UtilityController::class, 'index']);
$router->post('/admin/utilities/store', [\App\Controllers\UtilityController::class, 'store']);

// Private Files
$router->get('/files/ticket/{id}', [\App\Controllers\FileController::class, 'ticketAttachment']);
$router->get('/files/contract/{id}', [\App\Controllers\FileController::class, 'contractDocument']);

// Dispatch Request
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

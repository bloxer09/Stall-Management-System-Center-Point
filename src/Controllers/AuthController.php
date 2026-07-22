<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Core\Auth;
use App\Core\Session;
use App\Models\AuditLog;
use App\Services\MailjetMailer;
use App\Services\PhoneVerificationSender;
use App\Services\RegistrationVerification;
use App\Config\App;

class AuthController extends Controller
{
    private const REGISTER_PENDING_KEY = 'register_pending';
    private const REGISTER_FORM_KEY = 'register_form';
    private const REGISTER_CODE_TTL = 600;
    private const REGISTER_RESEND_COOLDOWN = 60;

    private function dashboardRouteForCurrentUser(): string
    {
        return Auth::isStaffOrAdmin() ? '/admin/dashboard' : '/user/dashboard';
    }

    public function showLoginForm()
    {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->dashboardRouteForCurrentUser());
            return;
        }
        $this->redirect('/?login=1');
    }

    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                $error = 'Invalid security token.';
                if ($this->isAjaxRequest()) {
                    return $this->jsonResponse(['status' => 'error', 'message' => $error], 403);
                }
                Session::setFlash('error', $error);
                $this->redirect('/?login=1');
                return;
            }

            // Simple Session-based Rate Limiting
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $throttleKey = 'login_attempts_' . md5($ip);
            $throttleTimeKey = 'login_time_' . md5($ip);

            $attempts = Session::get($throttleKey, 0);
            $lastAttempt = Session::get($throttleTimeKey, 0);

            // Block for 5 minutes after 5 failed attempts
            if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
                $minutesLeft = ceil((300 - (time() - $lastAttempt)) / 60);
                $error = "Too many failed attempts. Try again in {$minutesLeft} minute(s).";
                if ($this->isAjaxRequest()) {
                    return $this->jsonResponse(['status' => 'error', 'message' => $error], 429);
                }
                Session::setFlash('error', $error);
                $this->redirect('/?login=1');
                return;
            }

            // Reset attempts if block duration passed
            if ($attempts >= 5 && (time() - $lastAttempt) >= 300) {
                $attempts = 0;
            }

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                Session::unset($throttleKey);
                Session::unset($throttleTimeKey);

                $otpCode = RegistrationVerification::generateCode();
                $otpHash = RegistrationVerification::hashCode($otpCode);
                $now = time();

                Session::set('login_pending', [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'otp_hash' => $otpHash,
                    'expires_at' => $now + 600, // 10 minutes TTL
                    'resend_available_at' => $now + 60, // 60-second cooldown
                ]);

                (new MailjetMailer())->sendLoginOtpEmail($user['email'], $user['name'], $otpCode);

                $redirectPath = '/login/verify';
                if ($this->isAjaxRequest()) {
                    return $this->jsonResponse([
                        'status' => 'redirect',
                        'target' => App::path($redirectPath)
                    ]);
                }
                $this->redirect($redirectPath);
                return;
            } else {
                Session::set($throttleKey, $attempts + 1);
                Session::set($throttleTimeKey, time());
                if ($user) {
                    (new AuditLog())->logAction($user['id'], 'login_failed', 'User', $user['id'], ['ip' => $ip]);
                } else {
                    (new AuditLog())->logAction(null, 'login_failed', 'User', null, ['email' => $email, 'ip' => $ip]);
                }
                $error = 'Invalid email or password.';
                if ($this->isAjaxRequest()) {
                    return $this->jsonResponse(['status' => 'error', 'message' => $error], 401);
                }
                Session::setFlash('error', $error);
                // $this->redirect('/login');
            }
        }
    }

    public function showRegisterForm()
    {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->dashboardRouteForCurrentUser());
            return;
        }
        $this->redirect('/?register=1');
    }

    public function processRegister()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?register=1');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/?register=1');
            return;
        }

        $action = (string) ($_POST['registration_action'] ?? 'start');

        switch ($action) {
            case 'start':
                $this->startRegistration();
                return;
            case 'verify_email':
                $this->verifyRegistrationCode('email');
                return;
            case 'verify_phone':
                $this->verifyRegistrationCode('phone');
                return;
            case 'resend_codes':
                $this->resendRegistrationCodes();
                return;
            case 'complete':
                $this->completeRegistration();
                return;
            case 'reset':
                $this->resetRegistrationFlow();
                return;
            default:
                Session::setFlash('error', 'Invalid registration action.');
                $this->redirect('/register');
                return;
        }
    }

    private function startRegistration()
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phoneInput = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirmation'] ?? '');

        $this->storeRegisterFormData([
            'name' => $name,
            'email' => $email,
            'phone' => $phoneInput,
        ]);

        $phone = RegistrationVerification::normalizePhone($phoneInput);
        $errors = [];

        if ($name === '' || $email === '' || $phoneInput === '' || $password === '' || $passwordConfirm === '') {
            $errors[] = 'All fields are required.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        if ($phoneInput !== '' && $phone === null) {
            $errors[] = 'Enter a valid phone number.';
        }

        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!isset($_POST['terms'])) {
            $errors[] = 'You must agree to the Privacy Policy to register.';
        }

        $userModel = new User();
        if (!$userModel->supportsPhoneVerificationSchema()) {
            $error = 'Phone verification requires the latest database update. Run php update_database.php or update_database.cmd first.';
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse(['status' => 'error', 'message' => $error], 500);
            }
            Session::setFlash('error', $error);
            $this->redirect('/register');
            return;
        }

        if ($email !== '' && $userModel->findByEmail($email)) {
            $errors[] = 'Email is already registered.';
        }
        if ($phone !== null && $userModel->findByPhone($phone)) {
            $errors[] = 'Phone number is already registered.';
        }

        if ($errors !== []) {
            $errorMsg = implode(' ', array_unique($errors));
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse(['status' => 'error', 'message' => $errorMsg], 400);
            }
            Session::setFlash('error', $errorMsg);
            $this->redirect('/register');
            return;
        }

        $pendingRegistration = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'terms_accepted' => true,
            'started_at' => time(),
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ];

        $delivery = $this->issueVerificationCodes($pendingRegistration);
        if (!$delivery['ok']) {
            Session::setFlash('error', $delivery['message']);
            $this->redirect('/register');
            return;
        }

        $pendingRegistration = array_merge($pendingRegistration, $delivery['updates']);
        $this->storePendingRegistration($pendingRegistration);
        Session::unset(self::REGISTER_FORM_KEY);

        Session::setFlash('success', 'Verification codes sent. Verify both your email and phone number to complete registration.');
        $this->redirect('/register');
    }

    private function verifyRegistrationCode(string $channel)
    {
        $pendingRegistration = $this->getPendingRegistration();
        if (!$pendingRegistration) {
            Session::setFlash('warning', 'Start registration first so we can send verification codes.');
            $this->redirect('/register');
            return;
        }

        if (RegistrationVerification::isExpired($pendingRegistration)) {
            Session::setFlash('warning', 'Your verification codes have expired. Resend them to continue.');
            $this->redirect('/register');
            return;
        }

        $verifiedAtKey = $channel . '_verified_at';
        $hashKey = $channel . '_code_hash';
        $inputKey = $channel . '_code';
        $label = $channel === 'email' ? 'email' : 'phone';

        if (!empty($pendingRegistration[$verifiedAtKey])) {
            Session::setFlash('info', ucfirst($label) . ' is already verified.');
            $this->redirect('/register');
            return;
        }

        $submittedCode = trim((string) ($_POST[$inputKey] ?? ''));
        if (!preg_match('/^\d{6}$/', $submittedCode)) {
            Session::setFlash('error', 'Enter the 6-digit ' . $label . ' verification code.');
            $this->redirect('/register');
            return;
        }

        if (!RegistrationVerification::codesMatch($submittedCode, (string) ($pendingRegistration[$hashKey] ?? ''))) {
            Session::setFlash('error', 'Invalid ' . $label . ' verification code.');
            $this->redirect('/register');
            return;
        }

        $pendingRegistration[$verifiedAtKey] = date('Y-m-d H:i:s');
        unset($pendingRegistration[$hashKey]);
        $this->storePendingRegistration($pendingRegistration);

        $message = ucfirst($label) . ' verified successfully.';
        if ($this->registrationCanBeCompleted($pendingRegistration)) {
            $message = 'Both verification steps are complete. You can finish creating your account now.';
        }

        Session::setFlash('success', $message);
        $this->redirect('/register');
    }

    private function resendRegistrationCodes()
    {
        $pendingRegistration = $this->getPendingRegistration();
        if (!$pendingRegistration) {
            Session::setFlash('warning', 'Start registration first so we can send verification codes.');
            $this->redirect('/register');
            return;
        }

        if ($this->registrationCanBeCompleted($pendingRegistration)) {
            Session::setFlash('info', 'Both verification steps are already complete.');
            $this->redirect('/register');
            return;
        }

        $resendAvailableAt = (int) ($pendingRegistration['resend_available_at'] ?? 0);
        if ($resendAvailableAt > time()) {
            $secondsLeft = $resendAvailableAt - time();
            Session::setFlash('warning', 'Please wait ' . $secondsLeft . ' second(s) before resending codes.');
            $this->redirect('/register');
            return;
        }

        $delivery = $this->issueVerificationCodes($pendingRegistration);
        if (!$delivery['ok']) {
            Session::setFlash('error', $delivery['message']);
            $this->redirect('/register');
            return;
        }

        $pendingRegistration = array_merge($pendingRegistration, $delivery['updates']);
        $this->storePendingRegistration($pendingRegistration);

        Session::setFlash('success', 'New verification codes have been sent.');
        $this->redirect('/register');
    }

    private function completeRegistration()
    {
        $pendingRegistration = $this->getPendingRegistration();
        if (!$pendingRegistration) {
            Session::setFlash('warning', 'Start registration first so we can send verification codes.');
            $this->redirect('/register');
            return;
        }

        if (RegistrationVerification::isExpired($pendingRegistration)) {
            Session::setFlash('warning', 'Your verification codes have expired. Resend them to continue.');
            $this->redirect('/register');
            return;
        }

        if (!$this->registrationCanBeCompleted($pendingRegistration)) {
            Session::setFlash('error', 'Verify both your email and phone number before completing registration.');
            $this->redirect('/register');
            return;
        }

        $userModel = new User();
        if (!$userModel->supportsPhoneVerificationSchema()) {
            Session::setFlash('error', 'Phone verification requires the latest database update. Run php update_database.php or update_database.cmd first.');
            $this->resetRegistrationFlow(false);
            return;
        }

        if ($userModel->findByEmail($pendingRegistration['email'])) {
            Session::setFlash('error', 'That email address was registered while you were verifying. Please start over with a different email.');
            $this->resetRegistrationFlow(false);
            return;
        }

        if ($userModel->findByPhone($pendingRegistration['phone'])) {
            Session::setFlash('error', 'That phone number was registered while you were verifying. Please start over with a different number.');
            $this->resetRegistrationFlow(false);
            return;
        }

        $success = $userModel->createWithPasswordHash([
            'name' => $pendingRegistration['name'],
            'email' => $pendingRegistration['email'],
            'phone' => $pendingRegistration['phone'],
            'password_hash' => $pendingRegistration['password_hash'],
            'role' => 'user',
            'email_verified_at' => $pendingRegistration['email_verified_at'],
            'phone_verified_at' => $pendingRegistration['phone_verified_at'],
        ]);

        if (!$success) {
            Session::setFlash('error', 'Failed to create account. Please try again.');
            $this->redirect('/register');
            return;
        }

        $newUser = $userModel->findByEmail($pendingRegistration['email']);
        $this->clearRegistrationState();

        Auth::login($newUser);
        (new AuditLog())->logAction($newUser['id'], 'user_registered', 'User', $newUser['id'], [
            'email' => $pendingRegistration['email'],
            'phone' => $pendingRegistration['phone'],
            'email_verified_at' => $pendingRegistration['email_verified_at'],
            'phone_verified_at' => $pendingRegistration['phone_verified_at'],
        ]);
        Session::setFlash('success', 'Account created successfully. Welcome, ' . $pendingRegistration['name'] . '!');
        $this->redirect('/user/dashboard');
    }

    private function issueVerificationCodes(array $pendingRegistration): array
    {
        $updates = [
            'expires_at' => time() + self::REGISTER_CODE_TTL,
            'resend_available_at' => time() + self::REGISTER_RESEND_COOLDOWN,
        ];
        $notices = [];

        if (empty($pendingRegistration['email_verified_at'])) {
            $emailCode = RegistrationVerification::generateCode();
            $emailDelivery = (new MailjetMailer())->sendRegistrationVerificationEmail(
                (string) $pendingRegistration['email'],
                (string) $pendingRegistration['name'],
                $emailCode
            );

            if (empty($emailDelivery['sent'])) {
                return [
                    'ok' => false,
                    'message' => (string) ($emailDelivery['notice'] ?? 'Unable to send the email verification code.'),
                    'updates' => [],
                ];
            }

            $updates['email_code_hash'] = RegistrationVerification::hashCode($emailCode);
            if (!empty($emailDelivery['notice'])) {
                $notices[] = $emailDelivery['notice'];
            }
        }

        if (empty($pendingRegistration['phone_verified_at'])) {
            $phoneCode = RegistrationVerification::generateCode();
            $phoneDelivery = (new PhoneVerificationSender())->sendRegistrationCode(
                (string) $pendingRegistration['phone'],
                $phoneCode
            );

            if (empty($phoneDelivery['sent'])) {
                return [
                    'ok' => false,
                    'message' => (string) ($phoneDelivery['notice'] ?? 'Unable to send the phone verification code.'),
                    'updates' => [],
                ];
            }

            $updates['phone_code_hash'] = RegistrationVerification::hashCode($phoneCode);
            if (!empty($phoneDelivery['notice'])) {
                $notices[] = $phoneDelivery['notice'];
            }
        }

        $updates['delivery_notices'] = array_values(array_unique(array_filter($notices)));

        return [
            'ok' => true,
            'message' => '',
            'updates' => $updates,
        ];
    }

    private function registrationCanBeCompleted(array $pendingRegistration): bool
    {
        return !RegistrationVerification::isExpired($pendingRegistration)
            && !empty($pendingRegistration['email_verified_at'])
            && !empty($pendingRegistration['phone_verified_at']);
    }

    private function getPendingRegistration(): ?array
    {
        $pendingRegistration = Session::get(self::REGISTER_PENDING_KEY);
        return is_array($pendingRegistration) ? $pendingRegistration : null;
    }

    private function storePendingRegistration(array $pendingRegistration)
    {
        Session::set(self::REGISTER_PENDING_KEY, $pendingRegistration);
    }

    private function storeRegisterFormData(array $formData)
    {
        Session::set(self::REGISTER_FORM_KEY, $formData);
    }

    private function pullRegisterFormData(): array
    {
        $formData = Session::get(self::REGISTER_FORM_KEY, []);
        Session::unset(self::REGISTER_FORM_KEY);
        return is_array($formData) ? $formData : [];
    }

    private function clearRegistrationState()
    {
        Session::unset(self::REGISTER_PENDING_KEY);
        Session::unset(self::REGISTER_FORM_KEY);
    }

    private function resetRegistrationFlow(bool $withNotice = true)
    {
        $pendingRegistration = $this->getPendingRegistration();
        if ($pendingRegistration) {
            $this->storeRegisterFormData([
                'name' => $pendingRegistration['name'] ?? '',
                'email' => $pendingRegistration['email'] ?? '',
                'phone' => $pendingRegistration['phone'] ?? '',
            ]);
        }

        $this->clearRegistrationState();

        if ($withNotice) {
            Session::setFlash('info', 'Registration details cleared. Review your details and request fresh verification codes when you are ready.');
        }

        $this->redirect('/register');
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function logout()
    {
        $userId = Auth::isLoggedIn() ? Auth::user()['id'] : null;
        if ($userId) {
            (new AuditLog())->logAction($userId, 'logout', 'User', $userId);
        }
        Auth::logout();
        Session::setFlash('success', 'You have been logged out.');
        $this->redirect('/');
    }

    // ─────────────────────────────────────────────────────────────
    //  FORGOT PASSWORD
    // ─────────────────────────────────────────────────────────────

    public function showForgotPasswordForm()
    {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->dashboardRouteForCurrentUser());
        }
        return $this->view('pages/forgot_password', [
            'title' => App::pageTitle('Forgot Password')
        ]);
    }

    public function processForgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            $error = 'Invalid security token.';
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse(['status' => 'error', 'message' => $error], 403);
            }
            Session::setFlash('error', $error);
            $this->redirect('/forgot-password');
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Please enter your email address.';
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse(['status' => 'error', 'message' => $error], 400);
            }
            Session::setFlash('error', $error);
            $this->redirect('/forgot-password');
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        // Always show the same message to prevent email enumeration
        $genericMessage = 'If that email address is registered, you will receive a reset link shortly.';

        if ($user) {
            // Generate a cryptographically secure token
            $rawToken = bin2hex(random_bytes(32));       // 64-char hex string
            $hashedToken = hash('sha256', $rawToken);       // store only the hash
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $userModel->storeResetToken($user['id'], $hashedToken, $expiresAt);

            // Build the reset URL (raw token goes in URL, hash stored in DB)
            $resetLink = App::url('/reset-password?token=' . urlencode($rawToken));

            $mailer = new MailjetMailer();
            $sent = $mailer->sendPasswordResetEmail($user['email'], $user['name'], $resetLink);

            if (!$sent) {
                // Log silently; still show generic message to user
                error_log("MailjetMailer: failed to send reset email to {$user['email']}");
            }
        }

        Session::setFlash('success', $genericMessage);

        if ($this->isAjaxRequest()) {
            return $this->jsonResponse(['status' => 'success', 'message' => $genericMessage]);
        }

        $this->redirect('/forgot-password');
    }

    // ─────────────────────────────────────────────────────────────
    //  RESET PASSWORD
    // ─────────────────────────────────────────────────────────────

    public function showResetPasswordForm()
    {
        $rawToken = $_GET['token'] ?? '';

        if (empty($rawToken)) {
            Session::setFlash('error', 'Invalid or missing reset token.');
            $this->redirect('/forgot-password');
            return;
        }

        $hashedToken = hash('sha256', $rawToken);
        $userModel = new User();
        $user = $userModel->findByResetToken($hashedToken);

        if (!$user) {
            Session::setFlash('error', 'This reset link is invalid or has already been used.');
            $this->redirect('/forgot-password');
            return;
        }

        if (strtotime($user['token_expires_at']) < time()) {
            $userModel->clearResetToken($user['id']);
            Session::setFlash('error', 'This reset link has expired. Please request a new one.');
            $this->redirect('/forgot-password');
            return;
        }

        return $this->view('pages/reset_password', [
            'title' => App::pageTitle('Reset Password'),
            'token' => $rawToken,
        ]);
    }

    public function processResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?login=1');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/forgot-password');
            return;
        }

        $rawToken = $_POST['token'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirmation'] ?? '';

        if (empty($rawToken) || empty($newPassword)) {
            Session::setFlash('error', 'All fields are required.');
            $this->redirect('/forgot-password');
            return;
        }

        if (strlen($newPassword) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            $this->redirect('/reset-password?token=' . urlencode($rawToken));
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', 'Passwords do not match.');
            $this->redirect('/reset-password?token=' . urlencode($rawToken));
            return;
        }

        $hashedToken = hash('sha256', $rawToken);
        $userModel = new User();
        $user = $userModel->findByResetToken($hashedToken);

        if (!$user) {
            Session::setFlash('error', 'This reset link is invalid or has already been used.');
            $this->redirect('/forgot-password');
            return;
        }

        if (strtotime($user['token_expires_at']) < time()) {
            $userModel->clearResetToken($user['id']);
            Session::setFlash('error', 'This reset link has expired. Please request a new one.');
            $this->redirect('/forgot-password');
            return;
        }

        $userModel->updatePassword($user['id'], $newPassword);
        $userModel->clearResetToken($user['id']);
        (new AuditLog())->logAction($user['id'], 'password_reset_success', 'User', $user['id']);

        Session::setFlash('success', 'Your password has been reset successfully. Please log in with your new password.');
        $this->redirect('/?login=1');
    }

    public function showChangePasswordForm()
    {
        Auth::requireUser();

        return $this->view('pages/user/change_password', [
            'title' => 'Change Password'
        ]);
    }

    public function processChangePassword()
    {
        Auth::requireUser();
        $this->processChangePasswordFor('/user/dashboard');
    }

    public function showAdminChangePasswordForm()
    {
        Auth::requireStaffOrAdmin();

        return $this->view('pages/admin/change_password', [
            'title' => 'Change Password'
        ]);
    }

    public function processAdminChangePassword()
    {
        Auth::requireStaffOrAdmin();
        $this->processChangePasswordFor('/admin/dashboard');
    }

    private function processChangePasswordFor(string $redirectPath)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($redirectPath);
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect($redirectPath);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirmation'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::setFlash('error', 'All fields are required.');
            $this->redirect($redirectPath);
            return;
        }

        if (strlen($newPassword) < 8) {
            Session::setFlash('error', 'New password must be at least 8 characters long.');
            $this->redirect($redirectPath);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', 'New passwords do not match.');
            $this->redirect($redirectPath);
            return;
        }

        $userModel = new User();
        $currentUser = $userModel->findById(Auth::user()['id']);

        if (!$currentUser || !password_verify($currentPassword, $currentUser['password'])) {
            Session::setFlash('error', 'Your current password is incorrect.');
            $this->redirect($redirectPath);
            return;
        }

        if (password_verify($newPassword, $currentUser['password'])) {
            Session::setFlash('error', 'New password must be different from your current password.');
            $this->redirect($redirectPath);
            return;
        }

        // Generate and send verification code
        $otpCode = RegistrationVerification::generateCode();
        $mailer = new \App\Services\MailjetMailer();
        $mailResult = $mailer->sendPasswordChangeVerificationEmail($currentUser['email'], $currentUser['name'], $otpCode);
        
        if (isset($mailResult['sent']) && !$mailResult['sent']) {
            Session::setFlash('error', $mailResult['notice'] ?? 'Failed to send verification code. Please try again.');
            $this->redirect($redirectPath);
            return;
        }

        // Store pending details in session
        Session::set('password_change_otp_hash', RegistrationVerification::hashCode($otpCode));
        Session::set('password_change_otp_time', time());
        Session::set('password_change_pending_new_password', $newPassword);

        // Flash notice
        $notice = 'A verification code has been sent to your email. Please enter it to complete changing your password.';
        if (isset($mailResult['notice']) && $mailResult['notice'] !== null) {
            $notice .= ' (' . $mailResult['notice'] . ')';
        }
        Session::setFlash('info', $notice);

        $verifyPath = ($redirectPath === '/admin/dashboard') ? '/admin/change-password/verify' : '/user/change-password/verify';
        $this->redirect($verifyPath);
    }

    public function showChangePasswordVerifyForm()
    {
        Auth::requireUser();
        
        // Ensure there is a pending password change
        if (!Session::get('password_change_pending_new_password')) {
            Session::setFlash('error', 'No password change request in progress.');
            $this->redirect('/user/dashboard');
            return;
        }

        return $this->view('pages/user/change_password_verify', [
            'title' => 'Verify Password Change',
            'action' => \App\Config\App::basePath() . '/user/change-password/verify',
            'redirect' => \App\Config\App::basePath() . '/user/dashboard'
        ]);
    }

    public function processChangePasswordVerify()
    {
        Auth::requireUser();
        $this->processChangePasswordVerifyFor('/user/dashboard', '/user/change-password/verify');
    }

    public function showAdminChangePasswordVerifyForm()
    {
        Auth::requireStaffOrAdmin();
        
        // Ensure there is a pending password change
        if (!Session::get('password_change_pending_new_password')) {
            Session::setFlash('error', 'No password change request in progress.');
            $this->redirect('/admin/dashboard');
            return;
        }

        return $this->view('pages/admin/change_password_verify', [
            'title' => 'Verify Password Change',
            'action' => \App\Config\App::basePath() . '/admin/change-password/verify',
            'redirect' => \App\Config\App::basePath() . '/admin/dashboard'
        ]);
    }

    public function processAdminChangePasswordVerify()
    {
        Auth::requireStaffOrAdmin();
        $this->processChangePasswordVerifyFor('/admin/dashboard', '/admin/change-password/verify');
    }

    private function processChangePasswordVerifyFor(string $redirectPath, string $verifyPath)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($verifyPath);
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect($verifyPath);
            return;
        }

        $pendingNewPassword = Session::get('password_change_pending_new_password');
        $otpHash = Session::get('password_change_otp_hash');
        $otpTime = Session::get('password_change_otp_time');

        if (!$pendingNewPassword || !$otpHash || !$otpTime) {
            Session::setFlash('error', 'No password change request in progress.');
            $this->redirect($redirectPath);
            return;
        }

        // Check if OTP is expired (10 minutes)
        if (time() - $otpTime > 600) {
            Session::unset('password_change_pending_new_password');
            Session::unset('password_change_otp_hash');
            Session::unset('password_change_otp_time');
            Session::setFlash('error', 'Verification code has expired. Please submit the change password form again.');
            $this->redirect($redirectPath);
            return;
        }

        $submittedCode = $_POST['code'] ?? '';
        if (empty($submittedCode)) {
            Session::setFlash('error', 'Verification code is required.');
            $this->redirect($verifyPath);
            return;
        }

        if (!RegistrationVerification::codesMatch($submittedCode, $otpHash)) {
            Session::setFlash('error', 'Invalid verification code.');
            $this->redirect($verifyPath);
            return;
        }

        // OTP is correct! Update password
        $userModel = new User();
        $currentUser = $userModel->findById(Auth::user()['id']);

        if (!$currentUser) {
            Session::setFlash('error', 'User not found.');
            $this->redirect($redirectPath);
            return;
        }

        $userModel->updatePassword($currentUser['id'], $pendingNewPassword);
        (new AuditLog())->logAction($currentUser['id'], 'password_changed', 'User', $currentUser['id']);

        // Clean up session
        Session::unset('password_change_pending_new_password');
        Session::unset('password_change_otp_hash');
        Session::unset('password_change_otp_time');

        Session::setFlash('success', 'Your password has been changed successfully.');
        $this->redirect($redirectPath);
    }

    public function redirectToGoogle() {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->dashboardRouteForCurrentUser());
            return;
        }

        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';
        
        if (empty($clientId) || empty($redirectUri)) {
            Session::setFlash('error', 'Google Sign-In is not configured.');
            $this->redirect('/?login=1');
            return;
        }

        $state = bin2hex(random_bytes(16));
        Session::set('oauth_google_state', $state);

        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account'
        ]);

        $this->redirect($authUrl);
    }

    public function handleGoogleCallback() {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->dashboardRouteForCurrentUser());
            return;
        }

        $state = $_GET['state'] ?? '';
        $savedState = Session::get('oauth_google_state');
        Session::unset('oauth_google_state');

        if (empty($state) || $state !== $savedState) {
            Session::setFlash('error', 'Invalid Google state parameter.');
            $this->redirect('/?login=1');
            return;
        }

        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            Session::setFlash('error', 'Google authentication failed.');
            $this->redirect('/?login=1');
            return;
        }

        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';

        // Exchange code for token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) {
            Session::setFlash('error', 'Failed to retrieve access token from Google.');
            $this->redirect('/?login=1');
            return;
        }

        $data = json_decode($response, true);
        $accessToken = $data['access_token'] ?? '';

        if (empty($accessToken)) {
            Session::setFlash('error', 'Google returned empty access token.');
            $this->redirect('/?login=1');
            return;
        }

        // Fetch user profile info
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        $profileResponse = curl_exec($ch);
        $profileStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($profileStatus !== 200) {
            Session::setFlash('error', 'Failed to retrieve profile info from Google.');
            $this->redirect('/?login=1');
            return;
        }

        $profile = json_decode($profileResponse, true);
        $googleId = $profile['sub'] ?? '';
        $email = $profile['email'] ?? '';
        $name = $profile['name'] ?? 'Google User';
        $picture = $profile['picture'] ?? null;

        if (empty($googleId) || empty($email)) {
            Session::setFlash('error', 'Unable to retrieve your Google ID or email.');
            $this->redirect('/?login=1');
            return;
        }

        $userModel = new User();
        // 1. Try finding user by google_id
        $user = $userModel->findByGoogleId($googleId);

        if (!$user) {
            // 2. Try finding user by email
            $user = $userModel->findByEmail($email);
            if ($user) {
                // Link Google to existing account
                $userModel->linkGoogleProvider($user['id'], $googleId);
                $user = $userModel->findById($user['id']);
            } else {
                // First-time social sign-up: store details in session and redirect to terms consent
                Session::set('oauth_pending_registration', [
                    'provider' => 'google',
                    'google_id' => $googleId,
                    'name' => $name,
                    'email' => $email,
                    'profile_picture' => $picture,
                ]);
                $this->redirect('/auth/confirm-terms');
                return;
            }
        }

        // Log user in
        Auth::login($user);
        (new AuditLog())->logAction($user['id'], 'login_oauth_google', 'User', $user['id']);
        Session::setFlash('success', 'Logged in via Google. Welcome, ' . $user['name']);
        $this->redirect($this->dashboardRouteForCurrentUser());
    }

    // Login verification and OAuth compliance routes

    public function showLoginVerifyForm()
    {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->dashboardRouteForCurrentUser());
            return;
        }

        $loginPending = Session::get('login_pending');
        if (!$loginPending) {
            Session::setFlash('error', 'Please enter your login details first.');
            $this->redirect('/login');
            return;
        }

        $resendInSeconds = max(0, (int) ($loginPending['resend_available_at'] ?? 0) - time());
        $maskedEmail = RegistrationVerification::maskEmail((string) $loginPending['email']);

        return $this->view('pages/login_verify', [
            'title' => App::pageTitle('Login Verification'),
            'loginVerifyFlow' => [
                'masked_email' => $maskedEmail,
                'resend_in_seconds' => $resendInSeconds
            ]
        ]);
    }

    public function processLoginVerify()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login/verify');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/login/verify');
            return;
        }

        $loginPending = Session::get('login_pending');
        if (!$loginPending) {
            Session::setFlash('error', 'Verification session expired. Please sign in again.');
            $this->redirect('/?login=1');
            return;
        }

        if (time() > (int) ($loginPending['expires_at'] ?? 0)) {
            Session::unset('login_pending');
            Session::setFlash('error', 'Verification code expired. Please sign in again.');
            $this->redirect('/?login=1');
            return;
        }

        $code = $_POST['code'] ?? '';
        $codeHash = RegistrationVerification::hashCode($code);

        if ($codeHash === ($loginPending['otp_hash'] ?? '')) {
            $userModel = new User();
            $user = $userModel->findById($loginPending['user_id']);

            if (!$user) {
                Session::unset('login_pending');
                Session::setFlash('error', 'User account not found.');
                $this->redirect('/?login=1');
                return;
            }

            // Successfully authenticated, clear OTP session
            Session::unset('login_pending');

            Auth::login($user);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            (new AuditLog())->logAction($user['id'], 'login_success', 'User', $user['id'], ['ip' => $ip]);
            Session::setFlash('success', 'Welcome back, ' . $user['name']);

            $this->redirect($this->dashboardRouteForCurrentUser());
        } else {
            Session::setFlash('error', 'Invalid verification code.');
            $this->redirect('/login/verify');
        }
    }

    public function resendLoginOtp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login/verify');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/login/verify');
            return;
        }

        $loginPending = Session::get('login_pending');
        if (!$loginPending) {
            Session::setFlash('error', 'Verification session expired. Please sign in again.');
            $this->redirect('/?login=1');
            return;
        }

        $now = time();
        $resendAvailableAt = (int) ($loginPending['resend_available_at'] ?? 0);
        if ($now < $resendAvailableAt) {
            $secondsLeft = $resendAvailableAt - $now;
            Session::setFlash('error', "Please wait {$secondsLeft} second(s) before resending.");
            $this->redirect('/login/verify');
            return;
        }

        // Generate and send new OTP
        $otpCode = RegistrationVerification::generateCode();
        $otpHash = RegistrationVerification::hashCode($otpCode);

        $loginPending['otp_hash'] = $otpHash;
        $loginPending['expires_at'] = $now + 600;
        $loginPending['resend_available_at'] = $now + 60; // 60 seconds cooldown

        Session::set('login_pending', $loginPending);

        (new MailjetMailer())->sendLoginOtpEmail($loginPending['email'], $loginPending['name'], $otpCode);

        Session::setFlash('success', 'A new verification code has been sent to your email.');
        $this->redirect('/login/verify');
    }

    public function showOauthTermsForm()
    {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->dashboardRouteForCurrentUser());
            return;
        }

        $oauthPending = Session::get('oauth_pending_registration');
        if (!$oauthPending) {
            Session::setFlash('error', 'No pending social registration found.');
            $this->redirect('/?login=1');
            return;
        }

        return $this->view('pages/oauth_confirm_terms', [
            'title' => App::pageTitle('Complete Registration'),
            'oauthPending' => $oauthPending
        ]);
    }

    public function processOauthTerms()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auth/confirm-terms');
            return;
        }

        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            Session::setFlash('error', 'Invalid security token.');
            $this->redirect('/auth/confirm-terms');
            return;
        }

        $oauthPending = Session::get('oauth_pending_registration');
        if (!$oauthPending) {
            Session::setFlash('error', 'No pending social registration found.');
            $this->redirect('/?login=1');
            return;
        }

        $terms = $_POST['terms'] ?? '';
        if (empty($terms)) {
            Session::setFlash('error', 'You must agree to the Terms & Conditions and Privacy Policy to continue.');
            $this->redirect('/auth/confirm-terms');
            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (empty($password) || strlen($password) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            $this->redirect('/auth/confirm-terms');
            return;
        }

        if ($password !== $passwordConfirmation) {
            Session::setFlash('error', 'Password and confirmation password do not match.');
            $this->redirect('/auth/confirm-terms');
            return;
        }

        $userModel = new User();

        // Check again to avoid duplicate creation
        $existingUser = $userModel->findByEmail($oauthPending['email']);
        if ($existingUser) {
            Session::unset('oauth_pending_registration');
            Session::setFlash('error', 'An account with this email already exists. Please login normally.');
            $this->redirect('/?login=1');
            return;
        }

        // Build user data
        $newUserData = [
            'name' => $oauthPending['name'],
            'email' => $oauthPending['email'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
            'profile_picture' => $oauthPending['profile_picture'] ?? null,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ];

        if ($oauthPending['provider'] === 'google') {
            $newUserData['google_id'] = $oauthPending['google_id'];
        } elseif ($oauthPending['provider'] === 'facebook') {
            $newUserData['facebook_id'] = $oauthPending['facebook_id'];
        }

        if ($userModel->create($newUserData)) {
            if ($oauthPending['provider'] === 'google') {
                $user = $userModel->findByGoogleId($oauthPending['google_id']);
            } else {
                $user = $userModel->findByFacebookId($oauthPending['facebook_id']);
            }

            Session::unset('oauth_pending_registration');

            Auth::login($user);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            (new AuditLog())->logAction($user['id'], 'register_oauth_success', 'User', $user['id'], ['ip' => $ip, 'provider' => $oauthPending['provider']]);
            Session::setFlash('success', 'Account created successfully! Welcome, ' . $user['name']);
            $this->redirect($this->dashboardRouteForCurrentUser());
        } else {
            Session::setFlash('error', 'Failed to create your account. Please try again.');
            $this->redirect('/auth/confirm-terms');
        }
    }
}

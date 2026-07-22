<?php

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Core\Session;
use App\Services\RegistrationVerification;

class PasswordChangeOTPTest extends TestCase
{
    private $testUserId = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        Session::start();
    }

    protected function tearDown(): void
    {
        if ($this->testUserId !== null) {
            $userModel = new User();
            $userModel->delete($this->testUserId);
        }
        Session::unset('password_change_otp_hash');
        Session::unset('password_change_otp_time');
        Session::unset('password_change_pending_new_password');
        parent::tearDown();
    }

    public function testPasswordChangeOTPFlow(): void
    {
        $userModel = new User();
        $email = 'pwd_otp_' . uniqid() . '@example.com';
        $originalPassword = 'old-password123';
        $newPassword = 'new-password999';

        // 1. Create a test user
        $success = $userModel->create([
            'name' => 'OTP Pwd User',
            'email' => $email,
            'password' => $originalPassword,
            'role' => 'user'
        ]);
        $this->assertTrue($success);

        $user = $userModel->findByEmail($email);
        $this->assertNotFalse($user);
        $this->testUserId = $user['id'];

        // 2. Generate the OTP and state variables
        $otpCode = RegistrationVerification::generateCode();
        $otpHash = RegistrationVerification::hashCode($otpCode);
        $now = time();

        Session::set('password_change_pending_new_password', $newPassword);
        Session::set('password_change_otp_hash', $otpHash);
        Session::set('password_change_otp_time', $now);

        // 3. Verify they are set correctly in Session
        $this->assertSame($newPassword, Session::get('password_change_pending_new_password'));
        $this->assertSame($otpHash, Session::get('password_change_otp_hash'));
        $this->assertSame($now, Session::get('password_change_otp_time'));

        // 4. Validate OTP correct match
        $submittedCode = $otpCode;
        $this->assertTrue(RegistrationVerification::codesMatch($submittedCode, Session::get('password_change_otp_hash')));

        // 5. Update user password mimicking completion
        $pendingNewPassword = Session::get('password_change_pending_new_password');
        $userModel->updatePassword($user['id'], $pendingNewPassword);

        // Retrieve user and confirm password has changed
        $updatedUser = $userModel->findById($user['id']);
        $this->assertTrue(password_verify($newPassword, $updatedUser['password']));
        $this->assertFalse(password_verify($originalPassword, $updatedUser['password']));
    }
}

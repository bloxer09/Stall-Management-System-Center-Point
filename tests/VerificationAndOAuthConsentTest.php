<?php

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Core\Session;
use App\Services\RegistrationVerification;

class VerificationAndOAuthConsentTest extends TestCase
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
        Session::unset('login_pending');
        Session::unset('oauth_pending_registration');
        parent::tearDown();
    }

    public function testLoginPendingSessionAndOtpValidation(): void
    {
        $userModel = new User();
        $email = 'login_otp_' . uniqid() . '@example.com';
        $password = 'password123';

        // 1. Create a test user
        $success = $userModel->create([
            'name' => 'OTP Login User',
            'email' => $email,
            'password' => $password,
            'role' => 'user'
        ]);
        $this->assertTrue($success);

        $user = $userModel->findByEmail($email);
        $this->assertNotFalse($user);
        $this->testUserId = $user['id'];

        // 2. Mock login pending state creation
        $otpCode = RegistrationVerification::generateCode();
        $otpHash = RegistrationVerification::hashCode($otpCode);
        $now = time();

        Session::set('login_pending', [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'otp_hash' => $otpHash,
            'expires_at' => $now + 600,
            'resend_available_at' => $now + 60,
        ]);

        $pending = Session::get('login_pending');
        $this->assertNotNull($pending);
        $this->assertSame($user['id'], $pending['user_id']);
        $this->assertSame($otpHash, $pending['otp_hash']);

        // 3. Verify OTP validation matches hash
        $enteredCodeHash = RegistrationVerification::hashCode($otpCode);
        $this->assertSame($pending['otp_hash'], $enteredCodeHash);

        // Verify invalid code mismatch
        $wrongCodeHash = RegistrationVerification::hashCode('000000');
        $this->assertNotSame($pending['otp_hash'], $wrongCodeHash);
    }

    public function testSocialRegistrationComplianceInterception(): void
    {
        // Mock intermediate Google authentication callback variables
        $googleId = 'google-id-' . uniqid();
        $email = 'social_consent_' . uniqid() . '@example.com';
        $name = 'Consent User';

        Session::set('oauth_pending_registration', [
            'provider' => 'google',
            'google_id' => $googleId,
            'name' => $name,
            'email' => $email,
            'profile_picture' => null,
        ]);

        $pending = Session::get('oauth_pending_registration');
        $this->assertNotNull($pending);
        $this->assertSame('google', $pending['provider']);
        $this->assertSame($email, $pending['email']);
        $this->assertSame($googleId, $pending['google_id']);

        // Finalize social registration mimicking processOauthTerms approval
        $userModel = new User();
        $newUserData = [
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
            'profile_picture' => $pending['profile_picture'],
            'email_verified_at' => date('Y-m-d H:i:s'),
            'google_id' => $pending['google_id'],
        ];

        $createSuccess = $userModel->create($newUserData);
        $this->assertTrue($createSuccess);

        $registeredUser = $userModel->findByGoogleId($googleId);
        $this->assertNotFalse($registeredUser);
        $this->testUserId = $registeredUser['id'];
        $this->assertSame($email, $registeredUser['email']);
    }
}

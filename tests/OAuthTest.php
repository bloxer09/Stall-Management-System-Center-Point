<?php

use PHPUnit\Framework\TestCase;
use App\Models\User;

class OAuthTest extends TestCase
{
    private $testUserId = null;

    protected function tearDown(): void
    {
        if ($this->testUserId !== null) {
            $userModel = new User();
            $userModel->delete($this->testUserId);
        }
        parent::tearDown();
    }

    public function testCanCreateUserWithoutPasswordForSocialLogin(): void
    {
        $userModel = new User();
        $email = 'oauth_test_' . uniqid() . '@example.com';
        
        $success = $userModel->create([
            'name' => 'OAuth User',
            'email' => $email,
            'google_id' => 'google-oauth-id-123',
            'role' => 'user'
        ]);

        $this->assertTrue($success);

        $user = $userModel->findByGoogleId('google-oauth-id-123');
        $this->assertNotFalse($user);
        $this->testUserId = $user['id'];
        
        $this->assertSame('OAuth User', $user['name']);
        $this->assertSame($email, $user['email']);
        $this->assertSame('google-oauth-id-123', $user['google_id']);
        $this->assertNotEmpty($user['password']); // Hashed placeholder password
    }

}

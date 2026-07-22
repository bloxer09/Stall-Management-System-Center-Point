<?php

use App\Core\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            Session::start();
        }
    }

    public function testCsrfTokenValidation(): void
    {
        $token = Session::csrfToken();

        $this->assertNotEmpty($token);
        $this->assertTrue(Session::validateCsrfToken($token));
        $this->assertFalse(Session::validateCsrfToken('invalid-token'));
    }
}

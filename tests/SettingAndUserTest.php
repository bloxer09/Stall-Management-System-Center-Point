<?php

use PHPUnit\Framework\TestCase;
use App\Models\Setting;
use App\Models\User;

class SettingAndUserTest extends TestCase
{
    public function testCanSetAndGetTermsAndConditions(): void
    {
        $settingModel = new Setting();
        
        // Save existing terms to restore later
        $originalTerms = $settingModel->get('terms_conditions', null);

        // Test set and get
        $testValue = '<h1>Test Terms</h1><p>Test paragraph.</p>';
        $success = $settingModel->set('terms_conditions', $testValue);
        
        $this->assertTrue($success);
        $this->assertSame($testValue, $settingModel->get('terms_conditions'));

        // Restore original value
        if ($originalTerms !== null) {
            $settingModel->set('terms_conditions', $originalTerms);
        }
    }

    public function testGetStaffAndAdminsQueriesSuccessfully(): void
    {
        $userModel = new User();
        $results = $userModel->getStaffAndAdmins();

        $this->assertIsArray($results);
        foreach ($results as $user) {
            $this->assertArrayHasKey('role', $user);
            $this->assertTrue(in_array($user['role'], ['admin', 'staff'], true));
        }
    }
}

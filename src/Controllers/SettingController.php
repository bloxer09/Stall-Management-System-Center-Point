<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setting;
use App\Core\Auth;
use App\Core\Session;

class SettingController extends Controller {
    private $settingModel;

    public function __construct() {
        $this->settingModel = new Setting();
    }

    public function privacyForm() {
        Auth::requireAdmin();
        $currentPolicy = $this->settingModel->get('privacy_policy', '');
        
        return $this->view('pages/admin/settings/privacy_form', [
            'title' => 'Manage Privacy Policy',
            'policy' => $currentPolicy
        ]);
    }

    public function updatePrivacy() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/settings/privacy');
                return;
            }

            $policyContent = $_POST['privacy_policy'] ?? '';
            
            if ($this->settingModel->set('privacy_policy', $policyContent)) {
                Session::setFlash('success', 'Privacy Policy updated successfully.');
            } else {
                Session::setFlash('error', 'Failed to update Privacy Policy.');
            }
        }
        $this->redirect('/admin/settings/privacy');
    }

    public function termsForm() {
        Auth::requireAdmin();
        $currentTerms = $this->settingModel->get('terms_conditions', '');
        
        return $this->view('pages/admin/settings/terms_form', [
            'title' => 'Manage Terms & Conditions',
            'terms' => $currentTerms
        ]);
    }

    public function updateTerms() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
                Session::setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/settings/terms');
                return;
            }

            $termsContent = $_POST['terms_conditions'] ?? '';
            
            if ($this->settingModel->set('terms_conditions', $termsContent)) {
                Session::setFlash('success', 'Terms & Conditions updated successfully.');
            } else {
                Session::setFlash('error', 'Failed to update Terms & Conditions.');
            }
        }
        $this->redirect('/admin/settings/terms');
    }

    // Public view
    public function showPrivacyPolicy() {
        // Can be accessed without 'requireAdmin' context
        $settingModel = new Setting();
        $policy = $settingModel->get('privacy_policy', 'Privacy policy content has not been set yet.');

        return $this->view('pages/privacy', [
            'title' => 'Privacy Policy',
            'policy' => $policy
        ]);
    }

    public function showTermsConditions() {
        $settingModel = new Setting();
        $terms = $settingModel->get('terms_conditions', 'Terms & Conditions content has not been set yet.');

        return $this->view('pages/terms', [
            'title' => 'Terms & Conditions',
            'terms' => $terms
        ]);
    }
}

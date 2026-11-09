<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SystemSettings extends Component
{
    use WithFileUploads;

    public const DEFAULT_TERMS = "Center Point Commercial Stall Rental Terms and Conditions\n\n1. Lease Agreement\nTenants agree to comply with monthly rental payment schedules and building floor plan regulations.\n\n2. Payments\nRent is billed monthly. Invoices not paid by their due date are flagged as overdue.\n\n3. Termination\nLease termination or renewal requests must be submitted at least 30 days prior to contract expiration.";

    public string $platform_name = '';
    public string $platform_address = '';
    public string $support_email = '';
    public string $support_phone = '';
    public string $privacy_policy = '';
    public string $terms_conditions = '';
    public bool $sms_enabled = true;

    // Landing Background Image
    public $landing_bg_image = null;
    public ?string $existing_landing_bg_image = null;

    public function mount(): void
    {
        $this->platform_name = Setting::get('platform_name', 'Center Point Commercial Complex');
        $this->platform_address = Setting::get('platform_address', 'Pagadian City, Zamboanga del Sur');
        $this->support_email = Setting::get('support_email', 'support@stallm.com');
        $this->support_phone = Setting::get('support_phone', '09171112233');
        $this->existing_landing_bg_image = Setting::get('landing_bg_image');
        $this->privacy_policy = Setting::get('privacy_policy', "Center Point Commercial Stall Management Privacy Policy\n\n1. Data Collection\nWe collect tenant names, contact numbers, email addresses, and stall lease information required for property administration.\n\n2. Data Protection\nTenant payment transactions and personal credentials are encrypted using industry-standard protocols.\n\n3. Contact Information\nFor privacy inquiries, contact admin@stallm.com.");
        $this->terms_conditions = Setting::get('terms_conditions', self::DEFAULT_TERMS);
        $this->sms_enabled = Setting::get('sms_enabled', '1') === '1';
    }

    public function removeLandingBg(): void
    {
        if ($this->existing_landing_bg_image) {
            Storage::disk('public')->delete($this->existing_landing_bg_image);
            Setting::set('landing_bg_image', null);
            $this->existing_landing_bg_image = null;
            $this->dispatch('notify', type: 'success', message: 'Landing background image removed. Default theme gradient restored.');
        }
    }

    public function saveSettings(): void
    {
        $this->validate([
            'platform_name' => 'required|string|max:255',
            'platform_address' => 'required|string|max:255',
            'support_email' => 'required|email|max:255',
            'support_phone' => ['nullable', 'string', 'max:50', new \App\Rules\PhilippineMobileNumber],
            'privacy_policy' => 'required|string|min:20',
            'terms_conditions' => 'required|string|min:20',
            'landing_bg_image' => 'nullable|image|max:10240',
            'sms_enabled' => 'boolean',
        ]);

        if ($this->landing_bg_image) {
            $path = $this->landing_bg_image->store('branding', 'public');
            Setting::set('landing_bg_image', $path);
            $this->existing_landing_bg_image = $path;
            $this->landing_bg_image = null;
        }

        Setting::set('platform_name', $this->platform_name);
        Setting::set('platform_address', $this->platform_address);
        Setting::set('support_email', $this->support_email);
        Setting::set('support_phone', $this->support_phone);
        Setting::set('privacy_policy', $this->privacy_policy);
        Setting::set('terms_conditions', $this->terms_conditions);

        $smsWas = Setting::get('sms_enabled', '1') === '1';
        Setting::set('sms_enabled', $this->sms_enabled ? '1' : '0');
        if ($smsWas !== $this->sms_enabled) {
            AuditLog::record('Turned SMS notifications ' . ($this->sms_enabled ? 'on' : 'off'), 'Setting');
        }

        AuditLog::log(
            Auth::id(),
            "Updated System Settings & Branding",
            'Setting',
            1
        );

        $this->dispatch('notify', type: 'success', message: 'System settings saved successfully.');
    }

    public function createBackup(BackupService $backups): void
    {
        $name = $backups->create();
        AuditLog::record("Created database backup {$name}", 'Backup');
        $this->dispatch('notify', type: 'success', message: "Backup {$name} created.");
    }

    public function deleteBackup(string $name, BackupService $backups): void
    {
        if (!$backups->delete($name)) {
            $this->dispatch('notify', type: 'error', message: 'That backup no longer exists.');
            return;
        }

        AuditLog::record("Deleted database backup {$name}", 'Backup');
        $this->dispatch('notify', type: 'success', message: "Backup {$name} deleted.");
    }

    public function render(BackupService $backups)
    {
        return view('livewire.admin.system-settings', [
            'backups' => $backups->list(),
        ]);
    }
}

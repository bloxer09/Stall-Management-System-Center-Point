<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Console\Command;

class ResetTwoFactor extends Command
{
    protected $signature = 'stallm:2fa-reset {email}';

    protected $description = 'Turn off two-factor authentication for an admin who lost access to their phone';

    public function handle(): int
    {
        $admin = User::where('email', $this->argument('email'))->where('role', 'admin')->first();

        if (!$admin) {
            $this->error('No admin found with that email.');
            return self::FAILURE;
        }

        $admin->forceFill([
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ])->save();
        TwoFactorCode::where('user_id', $admin->id)->delete();

        AuditLog::record("Two-factor authentication reset for {$admin->email}", 'User', $admin->id, 'console', null);

        $this->info("Two-factor authentication turned off for {$admin->email}.");

        return self::SUCCESS;
    }
}

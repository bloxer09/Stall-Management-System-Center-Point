<?php

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (!$app->environment('local')) {
    throw new RuntimeException('This test fixture can only run in the local environment.');
}

$tenant = User::where('email', 'winly@gmail.com')->where('role', 'user')->firstOrFail();
$lease = StallMapping::with('stall')->where('user_id', $tenant->id)
    ->whereHas('stall', fn ($q) => $q->where('stall_number', 'F-05'))->firstOrFail();
if (!$lease->is_active || in_array($lease->renewal_status, ['requested', 'awaiting_payment'], true)) {
    throw new RuntimeException('The lease is inactive or already has a pending renewal. No changes made.');
}

$original = $lease->getAttributes();
$backup = storage_path('app/renewal-demo-backups/lease-'.$lease->id.'-request-'.now()->format('Ymd-His').'.json');
File::ensureDirectoryExists(dirname($backup));
File::put($backup, json_encode(['purpose' => 'Backup before creating pending renewal test fixture', 'lease' => $original], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
Auth::setUser($tenant);
$requestedEnd = $lease->end_date->copy()->addMonthNoOverflow();
$note = 'Test renewal request for final defense. Seeded before the normal renewal window; current lease dates remain unchanged.';

DB::transaction(function () use ($lease, $tenant, $requestedEnd, $note, $original) {
    $locked = StallMapping::whereKey($lease->id)->lockForUpdate()->firstOrFail();
    if ($locked->getAttributes() !== $original) {
        throw new RuntimeException('Lease changed since the backup. No test request created.');
    }
    $locked->update([
        'renewal_status' => 'requested', 'requested_end_date' => $requestedEnd->toDateString(),
        'renewal_note' => $note, 'renewal_rejection_reason' => null,
        'renewal_months' => 1, 'renewal_invoice_id' => null,
    ]);
    AuditLog::record('Created pending renewal test fixture for Lease #'.$lease->id, 'StallMapping', $lease->id, $note);
    foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
        Notification::send($adminId, 'renewal', 'Lease renewal requested',
            $tenant->name.' asked to renew Stall F-05 until '.$requestedEnd->format('M d, Y').' (test request).',
            route('admin.mappings'));
    }
});

$saved = $lease->fresh();
if ($saved->end_date->toDateString() !== $lease->end_date->toDateString()
    || $saved->payment_status !== $lease->payment_status
    || $saved->renewal_status !== 'requested' || $saved->renewal_months !== 1) {
    throw new RuntimeException('Test request verification failed; original record backup: '.$backup);
}
echo json_encode([
    'tenant' => $tenant->name, 'stall' => 'F-05', 'lease_id' => $saved->id,
    'status' => $saved->renewal_status, 'months' => $saved->renewal_months,
    'current_end' => $saved->end_date->toDateString(),
    'requested_end' => $saved->requested_end_date->toDateString(),
    'renewal_invoice_id' => $saved->renewal_invoice_id, 'backup' => $backup,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

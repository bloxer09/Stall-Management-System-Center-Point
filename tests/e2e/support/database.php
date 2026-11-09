<?php

use App\Models\Setting;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$expected = realpath(__DIR__.'/../.runtime').DIRECTORY_SEPARATOR.'e2e.sqlite';
if (!$app->environment('testing') || config('database.default') !== 'sqlite'
    || config('database.connections.sqlite.database') !== $expected
    || realpath(storage_path()) !== realpath(__DIR__.'/../.runtime')
    || config('cache.stores.file.path') !== storage_path('framework/cache/data')
    || config('session.files') !== storage_path('framework/sessions')) {
    fwrite(STDERR, "Refusing to touch a database outside tests/e2e/.runtime/e2e.sqlite.\n");
    exit(1);
}

$command = $argv[1] ?? '';
if ($command === 'migrate') {
    touch($expected);
    $status = Artisan::call('migrate', ['--force' => true]);
    if ($status !== 0) {
        fwrite(STDERR, Artisan::output());
        exit($status);
    }
    echo json_encode(['migrated' => true]);
    exit;
}

if ($command === 'reset') {
    DB::statement('PRAGMA foreign_keys = OFF');
    try {
        DB::transaction(function () {
            foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name <> 'migrations'") as $table) {
                DB::table($table->name)->delete();
            }
            DB::table('sqlite_sequence')->where('name', '!=', 'migrations')->delete();
        });
    } finally {
        DB::statement('PRAGMA foreign_keys = ON');
    }
    Cache::flush();
    foreach (glob(storage_path('framework/sessions/*')) ?: [] as $session) {
        if (is_file($session)) {
            unlink($session);
        }
    }
    foreach ([['tenant', 'user'], ['other', 'user'], ['admin', 'admin'], ['secure', 'admin']] as [$name, $role]) {
        User::create([
            'name' => 'E2E '.ucfirst($name), 'email' => "$name@example.test",
            'phone' => '0900000000'.User::count(), 'role' => $role,
            'password' => 'Defense!2026', 'email_verified_at' => now(),
            'two_factor_enabled' => $name === 'secure',
            'two_factor_confirmed_at' => $name === 'secure' ? now() : null,
        ]);
    }
    Setting::set('sms_enabled', '0');
    $floor = StallLocation::create(['name' => 'E2E Ground Floor', 'sort_order' => 1]);
    $stall = Stall::create(['stall_number' => 'QA-001', 'location_id' => $floor->id, 'price' => 1500, 'status' => 'occupied']);
    Stall::create(['stall_number' => 'QA-002', 'location_id' => $floor->id, 'price' => 1800, 'status' => 'available']);
    StallMapping::create([
        'user_id' => 1, 'stall_id' => $stall->id,
        'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addMonths(6)->toDateString(),
        'is_active' => true, 'payment_status' => 'good_standing',
    ]);
    $ticket = Ticket::create(['user_id' => 2, 'issue' => 'Private other tenant ticket', 'status' => 'open']);
    TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => 2, 'message' => 'Private other tenant message']);
    echo json_encode(['tenantId' => 1, 'adminId' => 3, 'availableStallId' => 2, 'privateTicketId' => $ticket->id]);
    exit;
}

if ($command === 'snapshot') {
    $result = [];
    foreach (['users', 'tickets', 'ticket_messages', 'announcements', 'notifications', 'stall_applications', 'stall_mappings', 'stalls', 'invoices', 'invoice_items', 'payment_transactions', 'audit_logs'] as $table) {
        $query = DB::table($table)->orderBy('id');
        $result[$table] = $table === 'users'
            ? $query->get(['id', 'name', 'email', 'phone', 'role', 'two_factor_enabled'])
            : $query->get();
    }
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit;
}
if ($command === 'renewal-fixture') {
    $lease = StallMapping::where('user_id', 1)->where('is_active', true)->firstOrFail();
    $lease->update(['end_date' => today()->addDays(30)->toDateString()]);
    echo json_encode(['leaseId' => $lease->id, 'endDate' => $lease->getRawOriginal('end_date')]);
    exit;
}
fwrite(STDERR, "Expected migrate, reset, or snapshot.\n");
exit(1);

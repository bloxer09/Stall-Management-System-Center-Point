<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Realistic demo data for presentations. Safe to run repeatedly (updateOrCreate everywhere,
 * nothing is deleted) and refuses to run in production.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private const FLOORS = [
        ['name' => 'Ground Floor', 'description' => 'High foot-traffic retail arcade by the main entrance.', 'rows' => 6, 'cols' => 8, 'prefix' => 'G', 'count' => 14, 'price' => 6500],
        ['name' => 'Second Floor', 'description' => 'Specialty shops, services, and boutiques.', 'rows' => 5, 'cols' => 6, 'prefix' => 'S', 'count' => 8, 'price' => 4500],
        ['name' => 'Food Court', 'description' => 'Food counters with shared dining area.', 'rows' => 4, 'cols' => 5, 'prefix' => 'F', 'count' => 8, 'price' => 5000],
    ];

    /** Stall numbers skipped because a real (non-demo) account already uses them. */
    private array $protectedStalls = [];

    /** Ground floor stalls left off the grid so the grid editor has something to place. */
    private const UNPLACED = ['G-13', 'G-14'];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder must not run in production.');
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@stallm.com'],
            ['name' => 'System Administrator', 'password' => Hash::make('password'), 'role' => 'admin', 'email_verified_at' => now()]
        );

        $stalls = $this->seedFloorsAndStalls();
        $tenants = $this->seedTenants();

        $plans = [
            1 => ['G-01', now()->addDays(45), []],
            2 => ['G-02', now()->addDays(120), ['renewal_status' => 'requested', 'requested_end_date' => now()->addDays(120)->addYear()->toDateString(), 'renewal_note' => 'Business is growing — we would like to stay another year.']],
            3 => ['G-03', now()->addYear(), []],
            4 => ['G-04', now()->addDays(200), []],
        ];

        $leases = [];
        foreach ($plans as $n => [$stallNumber, $endDate, $extra]) {
            if (in_array($stallNumber, $this->protectedStalls, true)) {
                $this->command?->warn("Skipped demo tenant{$n}: Stall {$stallNumber} already belongs to a real account.");
                continue;
            }
            $leases[$n] = $this->lease($tenants[$n], $stalls[$stallNumber], $endDate, $extra);
        }

        foreach ($leases as $lease) {
            for ($monthsAgo = 1; $monthsAgo <= 6; $monthsAgo++) {
                $this->paidInvoice($lease, now()->subMonthsNoOverflow($monthsAgo)->startOfMonth());
            }
        }
        if (isset($leases[3])) {
            $this->pendingInvoice($leases[3], now()->addDays(7));   // tenant3: due next week
        }
        if (isset($leases[4])) {
            $this->pendingInvoice($leases[4], now()->subDays(10));  // tenant4: overdue
        }

        $this->seedTickets($tenants, $admin);
        $this->seedApplications($stalls, $admin);
    }

    /** @return array<string, Stall> keyed by stall number */
    private function seedFloorsAndStalls(): array
    {
        $stalls = [];

        foreach (self::FLOORS as $order => $floor) {
            $location = StallLocation::updateOrCreate(
                ['name' => $floor['name']],
                ['description' => $floor['description'], 'sort_order' => $order + 1, 'grid_rows' => $floor['rows'], 'grid_cols' => $floor['cols']]
            );

            $cell = 0;
            for ($i = 1; $i <= $floor['count']; $i++) {
                $number = sprintf('%s-%02d', $floor['prefix'], $i);
                $placed = !in_array($number, self::UNPLACED, true);
                // Leave every third cell empty so the floor plan looks like a real layout.
                if ($placed && $cell % 3 === 2) {
                    $cell++;
                }
                $row = $placed ? intdiv($cell, $floor['cols']) + 1 : null;
                $col = $placed ? ($cell % $floor['cols']) + 1 : null;
                if ($placed) {
                    $cell++;
                }

                $existing = Stall::where('stall_number', $number)->first();
                if ($existing && $this->usedByRealAccount($existing)) {
                    // Never repurpose a stall someone actually leases or is billed for.
                    $this->protectedStalls[] = $number;
                    $stalls[$number] = $existing;
                    continue;
                }

                $stalls[$number] = Stall::updateOrCreate(
                    ['stall_number' => $number],
                    [
                        'location_id' => $location->id,
                        'status' => $this->statusFor($number),
                        'price' => $floor['price'],
                        'description' => "{$floor['name']} unit {$number}",
                        'grid_row' => $row,
                        'grid_col' => $col,
                    ]
                );
            }
        }

        return $stalls;
    }

    private function usedByRealAccount(Stall $stall): bool
    {
        $isReal = fn ($q) => $q->where('email', 'not like', '%@demo.test');

        return StallMapping::where('stall_id', $stall->id)->whereHas('user', $isReal)->exists()
            || Invoice::where('stall_id', $stall->id)->whereHas('user', $isReal)->exists();
    }

    private function statusFor(string $number): string
    {
        return match ($number) {
            'G-01', 'G-02', 'G-03', 'G-04' => 'occupied',
            'G-05' => 'reserved',
            'S-03' => 'maintenance',
            default => 'available',
        };
    }

    /** @return array<int, User> tenant number => user */
    private function seedTenants(): array
    {
        $names = [1 => 'Juan Dela Cruz', 2 => 'Maria Clara Reyes', 3 => 'Jose Rizal Santos', 4 => 'Andres Bonifacio Lim'];
        $tenants = [];

        foreach ($names as $n => $name) {
            $email = "tenant{$n}@demo.test";
            $phone = "0917000000{$n}";
            // users.phone is unique; skip the number if a real account already uses it.
            $phoneTaken = User::where('phone', $phone)->where('email', '!=', $email)->exists();

            $tenants[$n] = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'phone' => $phoneTaken ? null : $phone, 'password' => Hash::make('password'), 'role' => 'user', 'email_verified_at' => now()]
            );
        }

        return $tenants;
    }

    private function lease(User $tenant, Stall $stall, Carbon $endDate, array $extra = []): StallMapping
    {
        return StallMapping::updateOrCreate(
            ['user_id' => $tenant->id, 'stall_id' => $stall->id],
            array_merge([
                'start_date' => now()->subMonths(7)->startOfMonth()->toDateString(),
                'end_date' => $endDate->toDateString(),
                'is_active' => true,
                'payment_status' => 'good_standing',
                'renewal_status' => 'none',
            ], $extra)
        );
    }

    private function paidInvoice(StallMapping $lease, Carbon $period): void
    {
        $invoice = $this->invoice($lease, $period, $period->copy()->addDays(5), 'paid');
        $paidAt = $period->copy()->addDays(3)->setTime(10, 30);
        $invoice->forceFill(['paid_at' => $paidAt])->save();

        PaymentTransaction::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'user_id' => $lease->user_id,
                'gateway' => $period->month % 2 === 0 ? 'paymongo' : 'office_cash',
                'gateway_transaction_id' => 'DEMO-' . $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'status' => 'successful',
                'paid_at' => $paidAt,
            ]
        );
    }

    private function pendingInvoice(StallMapping $lease, Carbon $dueDate): void
    {
        $invoice = $this->invoice($lease, now()->startOfMonth(), $dueDate, 'pending');

        // Re-seeding after a rehearsal must put the bill back to unpaid and un-notified,
        // so the payment and overdue-notice flows can be demonstrated again.
        $invoice->forceFill(['paid_at' => null, 'overdue_notified_at' => null])->save();
        $invoice->transactions()->delete();
    }

    private function invoice(StallMapping $lease, Carbon $period, Carbon $dueDate, string $status): Invoice
    {
        $stall = $lease->stall;
        $label = 'Monthly Stall Rental - ' . $period->format('F Y');

        // invoices are unique per stall + billing period, so that is the natural key.
        $invoice = Invoice::updateOrCreate(
            ['stall_id' => $stall->id, 'billing_period' => $period->format('Y-m')],
            [
                'invoice_number' => 'INV-' . $period->format('Ym') . '-' . str_pad((string) $stall->id, 4, '0', STR_PAD_LEFT),
                'user_id' => $lease->user_id,
                'total_amount' => $stall->price,
                'status' => $status,
                'due_date' => $dueDate->toDateString(),
                'notes' => $label,
            ]
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $invoice->id, 'item_type' => 'rent'],
            ['description' => $label, 'amount' => $stall->price]
        );

        return $invoice;
    }

    /** @param array<int, User> $tenants */
    private function seedTickets(array $tenants, User $admin): void
    {
        $open = Ticket::updateOrCreate(
            ['user_id' => $tenants[1]->id, 'issue' => 'The ceiling light above my stall keeps flickering.'],
            ['stall_id' => Stall::where('stall_number', 'G-01')->value('id'), 'status' => 'open', 'assigned_to' => $admin->id]
        );
        TicketMessage::firstOrCreate(['ticket_id' => $open->id, 'user_id' => $tenants[1]->id, 'message' => 'The ceiling light above my stall keeps flickering.']);
        TicketMessage::firstOrCreate(['ticket_id' => $open->id, 'user_id' => $admin->id, 'message' => 'Thanks for reporting — maintenance will check it this afternoon.']);

        $closed = Ticket::updateOrCreate(
            ['user_id' => $tenants[3]->id, 'issue' => 'Requesting a copy of my lease contract.'],
            ['stall_id' => Stall::where('stall_number', 'G-03')->value('id'), 'status' => 'closed', 'assigned_to' => $admin->id]
        );
        TicketMessage::firstOrCreate(['ticket_id' => $closed->id, 'user_id' => $tenants[3]->id, 'message' => 'Requesting a copy of my lease contract.']);
    }

    /** @param array<string, Stall> $stalls */
    private function seedApplications(array $stalls, User $admin): void
    {
        $applications = [
            ['Carmen Villanueva', 'carmen.villanueva@example.com', '09181112222', 'Carmen\'s Kakanin', 'Food', 'F-02', 'pending', null],
            ['Ramon Bautista', 'ramon.bautista@example.com', '09182223333', 'RB Phone Accessories', 'Electronics', 'S-01', 'pending', null],
            ['Liza Mercado', 'liza.mercado@example.com', '09183334444', 'Liza Ukay-Ukay', 'Apparel', 'G-06', 'rejected', 'This stall is reserved for food and beverage tenants.'],
        ];

        foreach ($applications as [$name, $email, $phone, $business, $type, $stallNumber, $status, $reason]) {
            StallApplication::updateOrCreate(
                ['applicant_email' => $email, 'preferred_stall_id' => $stalls[$stallNumber]->id],
                [
                    'applicant_name' => $name,
                    'applicant_phone' => $phone,
                    'business_name' => $business,
                    'business_type' => $type,
                    'message' => 'Interested in a one-year lease.',
                    'status' => $status,
                    'rejection_reason' => $reason,
                    'reviewed_by' => $status === 'pending' ? null : $admin->id,
                    'reviewed_at' => $status === 'pending' ? null : now()->subDays(2),
                ]
            );
        }
    }
}

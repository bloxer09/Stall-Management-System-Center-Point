<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Core Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@stallm.com'],
            [
                'name' => 'Center Point Admin',
                'phone' => '09171112233',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'email_verified_at' => now(),
            ]
        );

        $tenants = [];
        $tenantNames = [
            'Juan Dela Cruz' => 'Cruz Grocery & General Merchandise',
            'Maria Santos' => 'Santos Apparel & Fashion Boutique',
            'Jose Rizal' => 'Rizal Bookshop & Printing',
            'Andres Bonifacio' => 'Katipunan Hardware & Supplies',
            'Melchora Aquino' => 'Tandang Sora Eatery & Refreshments',
        ];

        $index = 1;
        foreach ($tenantNames as $name => $business) {
            $email = "tenant{$index}@stallm.com";
            $tenants[] = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => '0918999' . str_pad($index, 4, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );
            $index++;
        }

        // 2. Create Stall Locations
        $locGround = StallLocation::firstOrCreate(
            ['name' => 'Ground Floor Arcade'],
            ['description' => 'High-foot-traffic retail and dry goods section', 'sort_order' => 1, 'grid_rows' => 6, 'grid_cols' => 8]
        );

        $locSecond = StallLocation::firstOrCreate(
            ['name' => 'Second Floor Mezzanine'],
            ['description' => 'Apparel, electronics, and specialty services', 'sort_order' => 2, 'grid_rows' => 6, 'grid_cols' => 8]
        );

        $locFoodCourt = StallLocation::firstOrCreate(
            ['name' => 'Food Court Zone'],
            ['description' => 'Dining area and food stalls', 'sort_order' => 3, 'grid_rows' => 4, 'grid_cols' => 6]
        );

        // 3. Create Stalls
        $stalls = [];
        // Ground Floor Stalls
        for ($i = 1; $i <= 12; $i++) {
            $num = 'GF-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $row = floor(($i - 1) / 4) + 1;
            $col = (($i - 1) % 4) + 1;
            $status = $i <= 8 ? 'occupied' : ($i == 9 ? 'maintenance' : 'available');
            $stalls[] = Stall::firstOrCreate(
                ['stall_number' => $num],
                [
                    'location_id' => $locGround->id,
                    'price' => 7500.00 + ($i * 250),
                    'status' => $status,
                    'grid_row' => $row,
                    'grid_col' => $col,
                    'description' => "Commercial retail stall space located on Ground Floor unit {$num}.",
                ]
            );
        }

        // Second Floor Stalls
        for ($i = 1; $i <= 10; $i++) {
            $num = '2F-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $row = floor(($i - 1) / 4) + 1;
            $col = (($i - 1) % 4) + 1;
            $status = $i <= 5 ? 'occupied' : 'available';
            $stalls[] = Stall::firstOrCreate(
                ['stall_number' => $num],
                [
                    'location_id' => $locSecond->id,
                    'price' => 6000.00 + ($i * 200),
                    'status' => $status,
                    'grid_row' => $row,
                    'grid_col' => $col,
                    'description' => "Spacious unit located on Second Floor Mezzanine {$num}.",
                ]
            );
        }

        // Food Court Stalls
        for ($i = 1; $i <= 6; $i++) {
            $num = 'FC-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $stalls[] = Stall::firstOrCreate(
                ['stall_number' => $num],
                [
                    'location_id' => $locFoodCourt->id,
                    'price' => 8500.00,
                    'status' => $i <= 4 ? 'occupied' : 'available',
                    'grid_row' => 1,
                    'grid_col' => $i,
                    'description' => "Dedicated food counter stall unit {$num} with utility hookups.",
                ]
            );
        }

        // 4. Create Stall Mappings (Active & Past Leases)
        $occupiedStalls = Stall::where('status', 'occupied')->get();
        foreach ($occupiedStalls as $idx => $stall) {
            $tenant = $tenants[$idx % count($tenants)];
            StallMapping::firstOrCreate(
                ['stall_id' => $stall->id, 'user_id' => $tenant->id, 'is_active' => true],
                [
                    'start_date' => Carbon::now()->subMonths(rand(3, 11))->startOfMonth()->toDateString(),
                    'end_date' => Carbon::now()->addMonths(rand(1, 12))->endOfMonth()->toDateString(),
                    'renewal_status' => 'approved',
                ]
            );
        }

        // 5. Populate Monthly Invoices (12 months trend data for Chart.js)
        $methods = ['gcash', 'maya', 'card', 'cash'];
        foreach ($tenants as $tIndex => $tenant) {
            $activeMapping = StallMapping::where('user_id', $tenant->id)->where('is_active', true)->first();
            if (!$activeMapping) continue;

            $stall = $activeMapping->stall;

            // Past 12 Months Paid Transactions
            for ($m = 11; $m >= 1; $m--) {
                $monthDate = Carbon::now()->subMonths($m);
                $period = $monthDate->format('Y-m');
                $invoiceNum = 'INV-' . $monthDate->format('Ym') . '-' . str_pad($stall->id, 4, '0', STR_PAD_LEFT);

                $invoice = Invoice::firstOrCreate(
                    [
                        'user_id' => $tenant->id,
                        'stall_id' => $stall->id,
                        'billing_period' => $period,
                    ],
                    [
                        'invoice_number' => $invoiceNum,
                        'total_amount' => $stall->price,
                        'status' => 'paid',
                        'due_date' => $monthDate->endOfMonth()->toDateString(),
                        'paid_at' => $monthDate->copy()->day(rand(5, 25)),
                        'notes' => 'Monthly rental invoice settled in full.',
                    ]
                );

                InvoiceItem::firstOrCreate([
                    'invoice_id' => $invoice->id,
                    'description' => 'Monthly Stall Rental - ' . $monthDate->format('F Y'),
                ], [
                    'amount' => $stall->price,
                    'item_type' => 'rent',
                ]);

                PaymentTransaction::firstOrCreate([
                    'invoice_id' => $invoice->id,
                    'user_id' => $tenant->id,
                ], [
                    'gateway' => $methods[rand(0, 3)],
                    'gateway_transaction_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(4))),
                    'amount' => $stall->price,
                    'status' => 'successful',
                    'paid_at' => $invoice->paid_at,
                ]);
            }

            // Current Month Invoice (Some paid, some pending)
            $isPaidCurrent = $tIndex % 2 == 0;
            $currentPeriod = Carbon::now()->format('Y-m');
            $currentInvoiceNum = 'INV-' . Carbon::now()->format('Ym') . '-' . str_pad($stall->id, 4, '0', STR_PAD_LEFT);

            $currentInvoice = Invoice::firstOrCreate(
                [
                    'user_id' => $tenant->id,
                    'stall_id' => $stall->id,
                    'billing_period' => $currentPeriod,
                ],
                [
                    'invoice_number' => $currentInvoiceNum,
                    'total_amount' => $stall->price,
                    'status' => $isPaidCurrent ? 'paid' : 'pending',
                    'due_date' => Carbon::now()->endOfMonth()->toDateString(),
                    'paid_at' => $isPaidCurrent ? Carbon::now()->subDays(rand(1, 5)) : null,
                    'notes' => 'Current billing period invoice.',
                ]
            );

            InvoiceItem::firstOrCreate([
                'invoice_id' => $currentInvoice->id,
                'description' => 'Monthly Stall Rental - ' . Carbon::now()->format('F Y'),
            ], [
                'amount' => $stall->price,
                'item_type' => 'rent',
            ]);

            if ($isPaidCurrent) {
                PaymentTransaction::firstOrCreate([
                    'invoice_id' => $currentInvoice->id,
                    'user_id' => $tenant->id,
                ], [
                    'gateway' => $methods[rand(0, 2)],
                    'gateway_transaction_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(4))),
                    'amount' => $stall->price,
                    'status' => 'successful',
                    'paid_at' => $currentInvoice->paid_at,
                ]);
            }
        }

        // 6. Create Support Tickets and Threaded Chat Messages
        $ticketSubjects = [
            'Water Leakage near Stall Overhead Ceiling',
            'Electricity Meter Billing Inquiry for July',
            'Request for Additional Signage Authorization',
            'Air Conditioning Maintenance in Zone B',
            'Floor Tile Replacement Request',
        ];

        foreach ($ticketSubjects as $tIdx => $subject) {
            $tenant = $tenants[$tIdx % count($tenants)];
            $ticket = Ticket::firstOrCreate(
                ['issue' => $subject, 'user_id' => $tenant->id],
                [
                    'stall_id' => $stalls[$tIdx]->id ?? null,
                    'status' => $tIdx % 2 == 0 ? 'open' : 'closed',
                    'created_at' => Carbon::now()->subDays(rand(2, 10)),
                ]
            );

            TicketParticipant::firstOrCreate(['ticket_id' => $ticket->id, 'user_id' => $tenant->id]);
            TicketParticipant::firstOrCreate(['ticket_id' => $ticket->id, 'user_id' => $admin->id]);

            TicketMessage::firstOrCreate(
                ['ticket_id' => $ticket->id, 'message' => "Hello management, I am submitting a concern regarding {$subject}."]
            , [
                'user_id' => $tenant->id,
                'created_at' => $ticket->created_at,
            ]);

            TicketMessage::firstOrCreate(
                ['ticket_id' => $ticket->id, 'message' => "Thank you for reaching out. Our maintenance team has been notified."]
            , [
                'user_id' => $admin->id,
                'created_at' => $ticket->created_at->addHours(3),
            ]);
        }

        // 7. Create Public Stall Applications
        $applicationsData = [
            ['applicant_name' => 'Carlos P. Garcia', 'applicant_email' => 'carlos@garcia.ph', 'applicant_phone' => '09191112233', 'business_name' => 'Bohol Handicrafts', 'business_type' => 'Retail & Crafts', 'status' => 'pending'],
            ['applicant_name' => 'Ramon Magsaysay', 'applicant_email' => 'ramon@magsaysay.ph', 'applicant_phone' => '09192223344', 'business_name' => 'Magsaysay Motors & Spares', 'business_type' => 'Automotive Accessories', 'status' => 'approved'],
            ['applicant_name' => 'Diosdado Macapagal', 'applicant_email' => 'diosdado@macapagal.ph', 'applicant_phone' => '09193334455', 'business_name' => 'Pampanga Delicacies & Sweets', 'business_type' => 'Food & Beverage', 'status' => 'pending'],
            ['applicant_name' => 'Corazon Aquino', 'applicant_email' => 'cory@aquino.ph', 'applicant_phone' => '09194445566', 'business_name' => 'Yellow Ribbon Floral Shop', 'business_type' => 'Florist & Decor', 'status' => 'rejected'],
            ['applicant_name' => 'Fidel V. Ramos', 'applicant_email' => 'fidel@ramos.ph', 'applicant_phone' => '09195556677', 'business_name' => 'Centennial Security & CCTV Solutions', 'business_type' => 'Electronics & Security', 'status' => 'approved'],
        ];

        foreach ($applicationsData as $app) {
            StallApplication::firstOrCreate(
                ['applicant_email' => $app['applicant_email']],
                array_merge($app, [
                    'preferred_stall_id' => $stalls[rand(0, count($stalls) - 1)]->id,
                    'message' => 'Interested in leasing a commercial stall space for business expansion at Center Point.',
                ])
            );
        }

        // 8. Audit Logs
        AuditLog::create(['user_id' => $admin->id, 'action' => 'System Database Seeding Completed', 'entity_type' => 'System', 'entity_id' => 1, 'ip_address' => '127.0.0.1']);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'Generated Monthly Rental Invoices', 'entity_type' => 'Payment', 'entity_id' => 1, 'ip_address' => '127.0.0.1']);

        // 9. System Settings
        Setting::updateOrCreate(['setting_key' => 'privacy_policy'], ['setting_value' => 'Center Point Stall Management Privacy Policy']);
        Setting::updateOrCreate(['setting_key' => 'terms_conditions'], ['setting_value' => 'Center Point Stall Rental Terms and Conditions']);

        $this->command->info('Database successfully seeded with comprehensive demonstration data!');
    }
}

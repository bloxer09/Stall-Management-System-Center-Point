<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            $payments = DB::table('payments')->get();

            foreach ($payments as $p) {
                $invoiceNumber = 'INV-' . str_pad($p->id, 5, '0', STR_PAD_LEFT);

                // Check if invoice number already exists
                $existingInvoice = DB::table('invoices')->where('invoice_number', $invoiceNumber)->first();
                if ($existingInvoice) {
                    continue;
                }

                $status = match ($p->status) {
                    'paid' => 'paid',
                    'failed' => 'cancelled',
                    default => 'pending',
                };

                $invoiceId = DB::table('invoices')->insertGetId([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => $p->user_id,
                    'stall_id' => $p->stall_id,
                    'total_amount' => $p->amount,
                    'status' => $status,
                    'due_date' => $p->due_date ?? date('Y-m-d', strtotime($p->created_at)),
                    'paid_at' => $p->paid_at,
                    'billing_period' => null, // Leave null to avoid collision with unique_stall_billing_period constraint
                    'notes' => $p->payment_notes,
                    'created_at' => $p->created_at,
                    'updated_at' => $p->updated_at,
                ]);

                DB::table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'description' => $p->payment_notes ?: 'Stall Rental Invoice',
                    'amount' => $p->amount,
                    'item_type' => 'rent',
                    'created_at' => $p->created_at,
                    'updated_at' => $p->updated_at,
                ]);

                if ($p->status === 'paid' || !empty($p->paymongo_id) || !empty($p->payment_reference)) {
                    DB::table('payment_transactions')->insert([
                        'invoice_id' => $invoiceId,
                        'user_id' => $p->user_id,
                        'gateway' => $p->payment_method ?? 'cash',
                        'gateway_transaction_id' => $p->paymongo_id ?? $p->payment_reference ?? ('TXN-' . $invoiceId),
                        'amount' => $p->amount,
                        'status' => $p->status === 'paid' ? 'successful' : 'pending',
                        'payload' => json_encode(['migrated_from_payment_id' => $p->id]),
                        'paid_at' => $p->paid_at,
                        'created_at' => $p->created_at,
                        'updated_at' => $p->updated_at,
                    ]);
                }
            }

            // Drop old payments table after successful data migration
            Schema::dropIfExists('payments');
        }
    }

    public function down(): void
    {
        // No down migration needed as invoices table is now primary
    }
};

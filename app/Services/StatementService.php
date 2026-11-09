<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class StatementService
{
    /**
     * A tenant's ledger: every non-cancelled invoice is a charge, every successful payment a
     * credit, in date order (charges before credits on the same day) with a running balance.
     *
     * @return array{lines: list<array{date: \Illuminate\Support\Carbon, label: string, charge: float, credit: float, balance: float}>, charges: float, payments: float, balance: float}
     */
    public function forUser(User $user): array
    {
        $entries = [];

        $invoices = Invoice::with('stall')->where('user_id', $user->id)->where('status', '!=', 'cancelled')->get();
        foreach ($invoices as $invoice) {
            $entries[] = [
                'date' => $invoice->created_at,
                'kind' => 0,
                'label' => $invoice->invoice_number . ' — Stall ' . ($invoice->stall->stall_number ?? '—') . ($invoice->billing_period ? " ({$invoice->billing_period})" : ''),
                'charge' => (float) $invoice->total_amount,
                'credit' => 0.0,
            ];
        }

        $payments = PaymentTransaction::with('invoice')
            ->where('status', 'successful')
            ->whereHas('invoice', fn ($q) => $q->where('user_id', $user->id))
            ->get();
        foreach ($payments as $payment) {
            $entries[] = [
                'date' => $payment->paid_at ?? $payment->created_at,
                'kind' => 1,
                'label' => 'Payment for ' . ($payment->invoice->invoice_number ?? '—') . ' via ' . strtoupper(str_replace('_', ' ', $payment->gateway)) . ($payment->gateway_transaction_id ? " · {$payment->gateway_transaction_id}" : ''),
                'charge' => 0.0,
                'credit' => (float) $payment->amount,
            ];
        }

        usort($entries, fn ($a, $b) => [$a['date']->toDateString(), $a['kind'], $a['date']] <=> [$b['date']->toDateString(), $b['kind'], $b['date']]);

        $balance = 0.0;
        $lines = [];
        foreach ($entries as $entry) {
            $balance = round($balance + $entry['charge'] - $entry['credit'], 2);
            unset($entry['kind']);
            $lines[] = $entry + ['balance' => $balance];
        }

        $charges = round(array_sum(array_column($lines, 'charge')), 2);
        $credits = round(array_sum(array_column($lines, 'credit')), 2);

        return ['lines' => $lines, 'charges' => $charges, 'payments' => $credits, 'balance' => round($charges - $credits, 2)];
    }

    public function pdf(User $user): string
    {
        $html = View::make('pdf.statement', [
            'user' => $user,
            'statement' => $this->forUser($user),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}

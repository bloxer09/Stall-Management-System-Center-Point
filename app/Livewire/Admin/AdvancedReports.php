<?php

namespace App\Livewire\Admin;

use App\Models\Invoice;
use App\Models\Stall;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Livewire\Component;

class AdvancedReports extends Component
{
    public function exportCsv()
    {
        $invoices = Invoice::with(['user', 'stall', 'transactions'])->orderBy('created_at', 'desc')->get();
        $csvFileName = 'financial-report-' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Invoice Number', 'Tenant', 'Stall Number', 'Amount', 'Status', 'Payment Method', 'Paid At', 'Due Date'];

        $callback = function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $inv) {
                $lastTxn = $inv->transactions->last();
                fputcsv($file, [
                    $inv->invoice_number,
                    $inv->user->name ?? 'N/A',
                    $inv->stall->stall_number ?? 'N/A',
                    $inv->total_amount,
                    $inv->status,
                    $lastTxn->gateway ?? ($inv->status === 'paid' ? 'cash' : 'N/A'),
                    $inv->paid_at ? Carbon::parse($inv->paid_at)->format('Y-m-d H:i:s') : '',
                    $inv->due_date ? Carbon::parse($inv->due_date)->format('Y-m-d') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf()
    {
        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');
        $pendingAmount = Invoice::where('status', 'pending')->sum('total_amount');
        $totalStalls = Stall::count();
        $occupiedStalls = Stall::where('status', 'occupied')->count();

        $payments = Invoice::with(['user', 'stall'])->latest()->take(50)->get();

        $html = view('pdf.financial-report', [
            'totalRevenue' => $totalRevenue,
            'pendingAmount' => $pendingAmount,
            'totalStalls' => $totalStalls,
            'occupiedStalls' => $occupiedStalls,
            'payments' => $payments,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, 'financial-report-summary.pdf');
    }

    public function render()
    {
        $totalStalls = Stall::count();
        $occupiedStalls = Stall::where('status', 'occupied')->count();
        $vacantStalls = Stall::where('status', 'available')->count();
        $maintenanceStalls = Stall::where('status', 'maintenance')->count();

        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('total_amount');

        // Overdue Payment Aging
        $today = Carbon::today();
        $overdue30 = Invoice::with(['user', 'stall'])
            ->where('status', 'pending')
            ->where('due_date', '<', $today)
            ->where('due_date', '>=', $today->copy()->subDays(30))
            ->get();

        $overdue60 = Invoice::with(['user', 'stall'])
            ->where('status', 'pending')
            ->where('due_date', '<', $today->copy()->subDays(30))
            ->where('due_date', '>=', $today->copy()->subDays(60))
            ->get();

        $overdue90 = Invoice::with(['user', 'stall'])
            ->where('status', 'pending')
            ->where('due_date', '<', $today->copy()->subDays(60))
            ->get();

        // 12-month revenue trend aggregated query (DB-agnostic for MySQL and SQLite)
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' ? "strftime('%Y-%m', paid_at)" : "DATE_FORMAT(paid_at, '%Y-%m')";

        $rawTrend = Invoice::selectRaw("{$dateFormat} as month_key, SUM(total_amount) as revenue")
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw($dateFormat)
            ->pluck('revenue', 'month_key')
            ->toArray();

        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'revenue' => (float) ($rawTrend[$key] ?? 0),
            ];
        }

        return view('livewire.admin.advanced-reports', [
            'totalStalls' => $totalStalls,
            'occupiedStalls' => $occupiedStalls,
            'vacantStalls' => $vacantStalls,
            'maintenanceStalls' => $maintenanceStalls,
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'overdue30' => $overdue30,
            'overdue60' => $overdue60,
            'overdue90' => $overdue90,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }
}

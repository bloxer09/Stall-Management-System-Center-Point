<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use App\Models\Stall;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Livewire\Component;

class AdvancedReports extends Component
{
    public function exportCsv()
    {
        $payments = Payment::with(['user', 'stall'])->orderBy('created_at', 'desc')->get();
        $csvFileName = 'financial-report-' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Payment ID', 'Tenant', 'Stall Number', 'Amount', 'Status', 'Payment Method', 'Paid At', 'Due Date'];

        $callback = function () use ($payments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($payments as $p) {
                fputcsv($file, [
                    $p->id,
                    $p->user->name ?? 'N/A',
                    $p->stall->stall_number ?? 'N/A',
                    $p->amount,
                    $p->status,
                    $p->payment_method ?? 'N/A',
                    $p->paid_at ? Carbon::parse($p->paid_at)->format('Y-m-d H:i:s') : '',
                    $p->due_date ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf()
    {
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $pendingAmount = Payment::where('status', 'pending')->sum('amount');
        $totalStalls = Stall::count();
        $occupiedStalls = Stall::where('status', 'occupied')->count();

        $payments = Payment::with(['user', 'stall'])->latest()->take(50)->get();

        $html = view('pdf.financial-report', [
            'totalRevenue' => $totalRevenue,
            'pendingAmount' => $pendingAmount,
            'totalStalls' => $totalStalls,
            'occupiedStalls' => $occupiedStalls,
            'payments' => $payments,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
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

        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('amount');

        // Overdue Payment Aging
        $today = Carbon::today();
        $overdue30 = Payment::with(['user', 'stall'])
            ->where('status', 'pending')
            ->where('due_date', '<', $today)
            ->where('due_date', '>=', $today->copy()->subDays(30))
            ->get();

        $overdue60 = Payment::with(['user', 'stall'])
            ->where('status', 'pending')
            ->where('due_date', '<', $today->copy()->subDays(30))
            ->where('due_date', '>=', $today->copy()->subDays(60))
            ->get();

        $overdue90 = Payment::with(['user', 'stall'])
            ->where('status', 'pending')
            ->where('due_date', '<', $today->copy()->subDays(60))
            ->get();

        // 12-month revenue trend
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $rev = Payment::where('status', 'paid')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('amount');

            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'revenue' => (float)$rev,
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

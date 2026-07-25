<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\StallLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public function exportCsv()
    {
        AuditLog::log(Auth::id(), 'Exported Analytics CSV Report', 'Analytics', 1);

        $payments = Payment::with(['user', 'stall'])->where('status', 'paid')->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics_revenue_report_' . date('Y_m_d') . '.csv"',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Payment ID', 'Tenant Name', 'Tenant Email', 'Stall Number', 'Amount (PHP)', 'Method', 'Reference', 'Paid Date']);

            foreach ($payments as $p) {
                fputcsv($file, [
                    '#PAY-' . $p->id,
                    $p->user->name ?? 'N/A',
                    $p->user->email ?? 'N/A',
                    $p->stall->stall_number ?? 'N/A',
                    $p->amount,
                    strtoupper($p->payment_method ?? 'CASH'),
                    $p->payment_reference ?? 'N/A',
                    $p->paid_at ? Carbon::parse($p->paid_at)->format('Y-m-d H:i') : '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf()
    {
        AuditLog::log(Auth::id(), 'Exported Analytics PDF Summary Report', 'Analytics', 1);

        return redirect()->route('admin.reports');
    }

    public function render()
    {
        $totalStalls = Stall::count();
        $occupiedStalls = Stall::where('status', 'occupied')->count();
        $availableStalls = Stall::where('status', 'available')->count();
        $maintenanceStalls = Stall::where('status', 'maintenance')->count();
        $occupancyRate = $totalStalls > 0 ? round(($occupiedStalls / $totalStalls) * 100, 1) : 0;

        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('amount');

        // 12-month revenue breakdown for Chart.js
        $monthsLabels = [];
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = Payment::where('status', 'paid')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('amount');

            $monthsLabels[] = $month->format('M Y');
            $revenueData[] = (float) $revenue;
        }

        // Occupancy breakdown by Floor Location
        $locations = StallLocation::withCount(['stalls', 'stalls as occupied_stalls_count' => function ($q) {
            $q->where('status', 'occupied');
        }])->get();

        $locationLabels = $locations->pluck('name')->toArray();
        $locationTotal = $locations->pluck('stalls_count')->toArray();
        $locationOccupied = $locations->pluck('occupied_stalls_count')->toArray();

        return view('livewire.admin.analytics-dashboard', [
            'totalStalls' => $totalStalls,
            'occupiedStalls' => $occupiedStalls,
            'availableStalls' => $availableStalls,
            'maintenanceStalls' => $maintenanceStalls,
            'occupancyRate' => $occupancyRate,
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'monthsLabels' => $monthsLabels,
            'revenueData' => $revenueData,
            'locationLabels' => $locationLabels,
            'locationTotal' => $locationTotal,
            'locationOccupied' => $locationOccupied,
        ]);
    }
}

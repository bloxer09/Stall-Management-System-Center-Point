<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public function exportXlsx(\App\Services\XlsxExportService $exporter)
    {
        AuditLog::record('Exported Analytics XLSX Report', 'Analytics', 1);

        $invoices = Invoice::with(['user', 'stall', 'transactions'])->where('status', 'paid')->latest()->get();
        $filename = 'analytics_revenue_report_' . date('Y_m_d_His') . '.xlsx';

        $headers = ['Invoice Number', 'Tenant Name', 'Tenant Email', 'Stall Number', 'Amount (PHP)', 'Method', 'Reference', 'Paid Date'];
        $rows = [];

        foreach ($invoices as $inv) {
            $lastTxn = $inv->transactions->last();
            $rows[] = [
                $inv->invoice_number,
                $inv->user->name ?? 'N/A',
                $inv->user->email ?? 'N/A',
                $inv->stall->stall_number ?? 'N/A',
                $inv->total_amount,
                strtoupper($lastTxn->gateway ?? 'CASH'),
                $lastTxn->gateway_transaction_id ?? 'N/A',
                $inv->paid_at ? Carbon::parse($inv->paid_at)->format('Y-m-d H:i') : 'N/A',
            ];
        }

        return $exporter->downloadXlsx($filename, $headers, $rows);
    }

    public function exportPdf()
    {
        AuditLog::record('Exported Analytics PDF Summary Report', 'Analytics', 1);

        return redirect()->route('admin.reports');
    }

    public function render()
    {
        $totalStalls = Stall::count();
        $occupiedStalls = Stall::where('status', 'occupied')->count();
        $availableStalls = Stall::where('status', 'available')->count();
        $maintenanceStalls = Stall::where('status', 'maintenance')->count();
        $occupancyRate = $totalStalls > 0 ? round(($occupiedStalls / $totalStalls) * 100, 1) : 0;

        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('total_amount');

        // Aggregated 12-month revenue query (DB-agnostic for MySQL and SQLite)
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' ? "strftime('%Y-%m', paid_at)" : "DATE_FORMAT(paid_at, '%Y-%m')";

        $rawRevenue = Invoice::selectRaw("{$dateFormat} as month_key, SUM(total_amount) as revenue")
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw($dateFormat)
            ->pluck('revenue', 'month_key')
            ->toArray();

        $monthsLabels = [];
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $monthsLabels[] = $month->format('M Y');
            $revenueData[] = (float) ($rawRevenue[$key] ?? 0);
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

<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallApplication;
use App\Models\StallMapping;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public $timeframe = '12m'; // '30d', '6m', '12m', 'ytd'

    public function setTimeframe($tf)
    {
        $this->timeframe = in_array($tf, ['30d', '6m', '12m', 'ytd']) ? $tf : '12m';
    }

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

    /**
     * Work waiting on an admin, each linking to its pre-filtered list.
     *
     * @return array<int, array{label: string, count: int, url: string}>
     */
    private function attentionItems(): array
    {
        return [
            ['label' => 'Pending applications', 'count' => StallApplication::where('status', 'pending')->count(), 'url' => url('/admin/applications')],
            ['label' => 'Renewal requests', 'count' => StallMapping::where('is_active', true)->where('renewal_status', 'requested')->count(), 'url' => url('/admin/mappings?status=renewal_requested')],
            ['label' => 'Overdue invoices', 'count' => Invoice::overdue()->count(), 'url' => url('/admin/payments?status=overdue')],
            ['label' => 'Unassigned tickets', 'count' => Ticket::where('status', '!=', 'closed')->whereNull('assigned_to')->count(), 'url' => url('/admin/tickets?filter=unassigned')],
        ];
    }

    public function render()
    {
        // 1. Core Stall & Occupancy Metrics
        $totalStalls = Stall::count();
        $occupiedStalls = Stall::where('status', 'occupied')->count();
        $availableStalls = Stall::where('status', 'available')->count();
        $maintenanceStalls = Stall::where('status', 'maintenance')->count();
        $occupancyRate = $totalStalls > 0 ? round(($occupiedStalls / $totalStalls) * 100, 1) : 0;

        // 2. Financial Metrics
        $totalRevenue = (float) Invoice::where('status', 'paid')->sum('total_amount');
        $totalPaidInvoicesCount = Invoice::where('status', 'paid')->count();

        $monthlyRevenue = (float) Invoice::where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('total_amount');

        $monthlyPaidCount = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->count();

        // Monthly Target (Sum of prices of currently occupied stalls)
        $monthlyTarget = (float) Stall::where('status', 'occupied')->sum('price');
        $collectionRate = $monthlyTarget > 0 ? min(100, round(($monthlyRevenue / $monthlyTarget) * 100, 1)) : 100;

        // Pending & Overdue Receivables
        $pendingReceivables = (float) Invoice::where('status', 'pending')->sum('total_amount');
        $pendingInvoicesCount = Invoice::where('status', 'pending')->count();

        // 3. Lease Health
        $activeLeasesCount = StallMapping::where('is_active', true)->count();
        $expiringSoonCount = StallMapping::where('is_active', true)
            ->whereBetween('end_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->count();

        // 4. Revenue Trend by Selected Timeframe
        $driver = DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' ? "strftime('%Y-%m', paid_at)" : "DATE_FORMAT(paid_at, '%Y-%m')";

        $numMonths = match ($this->timeframe) {
            '30d' => 1,
            '6m' => 6,
            'ytd' => Carbon::now()->month,
            default => 12,
        };

        $rawRevenue = Invoice::selectRaw("{$dateFormat} as month_key, SUM(total_amount) as revenue, COUNT(id) as invoice_count")
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths($numMonths - 1)->startOfMonth())
            ->groupByRaw($dateFormat)
            ->get()
            ->keyBy('month_key');

        $monthsLabels = [];
        $revenueData = [];
        $invoiceCountData = [];

        for ($i = $numMonths - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $monthsLabels[] = $month->format('M Y');
            $revenueData[] = (float) ($rawRevenue[$key]->revenue ?? 0);
            $invoiceCountData[] = (int) ($rawRevenue[$key]->invoice_count ?? 0);
        }

        // 5. Payment Gateway / Method Split
        $gatewayCounts = PaymentTransaction::where('status', 'paid')
            ->selectRaw('gateway, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('gateway')
            ->get();

        $cashTotal = 0;
        $onlineTotal = 0;
        foreach ($gatewayCounts as $gw) {
            if (strtolower($gw->gateway ?? '') === 'cash') {
                $cashTotal += (float) $gw->total;
            } else {
                $onlineTotal += (float) $gw->total;
            }
        }
        $paymentMethodsData = [$onlineTotal, $cashTotal];

        // 6. Floor Performance Breakdown
        $locations = StallLocation::with(['stalls'])->orderBy('sort_order')->get();
        $floorPerformance = $locations->map(function ($loc) {
            $total = $loc->stalls->count();
            $occupied = $loc->stalls->where('status', 'occupied')->count();
            $available = $loc->stalls->where('status', 'available')->count();
            $potentialRent = $loc->stalls->sum('price');
            $activeRent = $loc->stalls->where('status', 'occupied')->sum('price');
            $occupancyPct = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

            return [
                'id' => $loc->id,
                'name' => $loc->name,
                'total_stalls' => $total,
                'occupied_stalls' => $occupied,
                'available_stalls' => $available,
                'potential_rent' => $potentialRent,
                'active_rent' => $activeRent,
                'occupancy_pct' => $occupancyPct,
            ];
        });

        $locationLabels = $floorPerformance->pluck('name')->toArray();
        $locationTotal = $floorPerformance->pluck('total_stalls')->toArray();
        $locationOccupied = $floorPerformance->pluck('occupied_stalls')->toArray();

        // 7. Upcoming Lease Expirations (Next 60 Days)
        $expiringLeases = StallMapping::with(['user', 'stall.location'])
            ->where('is_active', true)
            ->where('end_date', '>=', Carbon::now()->startOfDay())
            ->where('end_date', '<=', Carbon::now()->addDays(60)->endOfDay())
            ->orderBy('end_date')
            ->limit(5)
            ->get();

        // 8. Recent Settled Invoices
        $recentSettlements = Invoice::with(['user', 'stall.location', 'transactions'])
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(5)
            ->get();

        return view('livewire.admin.analytics-dashboard', [
            'attention' => $this->attentionItems(),
            'totalStalls' => $totalStalls,
            'occupiedStalls' => $occupiedStalls,
            'availableStalls' => $availableStalls,
            'maintenanceStalls' => $maintenanceStalls,
            'occupancyRate' => $occupancyRate,
            'totalRevenue' => $totalRevenue,
            'totalPaidInvoicesCount' => $totalPaidInvoicesCount,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyPaidCount' => $monthlyPaidCount,
            'monthlyTarget' => $monthlyTarget,
            'collectionRate' => $collectionRate,
            'pendingReceivables' => $pendingReceivables,
            'pendingInvoicesCount' => $pendingInvoicesCount,
            'activeLeasesCount' => $activeLeasesCount,
            'expiringSoonCount' => $expiringSoonCount,
            'monthsLabels' => $monthsLabels,
            'revenueData' => $revenueData,
            'invoiceCountData' => $invoiceCountData,
            'paymentMethodsData' => $paymentMethodsData,
            'locationLabels' => $locationLabels,
            'locationTotal' => $locationTotal,
            'locationOccupied' => $locationOccupied,
            'floorPerformance' => $floorPerformance,
            'expiringLeases' => $expiringLeases,
            'recentSettlements' => $recentSettlements,
        ]);
    }
}

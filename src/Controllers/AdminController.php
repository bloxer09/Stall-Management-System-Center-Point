<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;

class AdminController extends Controller {
    public function __construct() {
        Auth::requireStaffOrAdmin();
    }

    public function dashboard() {
        $stallModel = new \App\Models\Stall();
        $paymentModel = new \App\Models\Payment();
        $mappingModel = new \App\Models\Mapping();
        $ticketModel = new \App\Models\Ticket();

        // Ensure monthly invoices exist for the current cycle.
        $paymentModel->generateMonthlyInvoices();

        // Period filter for revenue (7days, 1month, 1year)
        $period = isset($_GET['period']) ? $_GET['period'] : '1month';
        $validPeriods = ['7days', '1month', '1year'];
        if (!in_array($period, $validPeriods)) {
            $period = '1month';
        }

        $totalStalls = $stallModel->getTotal();
        $occupiedStalls = $stallModel->getOccupiedCount();
        $pendingPayments = $paymentModel->getPendingCount();
        
        $revenueTotal = $paymentModel->getRevenueStats($period);
        $revenueChart = $paymentModel->getRevenueChartData($period);
        $delinquency = $paymentModel->getDelinquencyRate();
        
        // Ticket Statistics
        $ticketStats = [
            'total' => $ticketModel->getTotalCount(),
            'open' => $ticketModel->getCountByStatus('open'),
            'in_progress' => $ticketModel->getCountByStatus('in_progress'),
            'resolved' => $ticketModel->getCountByStatus('resolved'),
            'closed' => $ticketModel->getCountByStatus('closed')
        ];
        $ticketSla = $ticketModel->getSlaStats();

        $expiringLeases = $mappingModel->getExpiringLeases(30);

        // Format revenue data for Chart.js
        $chartLabels = [];
        $chartData = [];

        if ($revenueChart['type'] === 'monthly') {
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartLabels = $monthNames;
            $chartData = array_fill(0, 12, 0);
            foreach ($revenueChart['data'] as $row) {
                $chartData[$row['label_val'] - 1] = (float)$row['total'];
            }
        } else {
            // daily or daily_month
            foreach ($revenueChart['data'] as $row) {
                $chartLabels[] = date('M d', strtotime($row['label_val']));
                $chartData[] = (float)$row['total'];
            }
        }

        return $this->view('pages/admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => [
                'total_stalls' => $totalStalls,
                'occupied_stalls' => $occupiedStalls,
                'pending_payments' => $pendingPayments,
                'revenue_total' => $revenueTotal,
                'period' => $period,
                'delinquency_rate' => $delinquency['rate'],
                'overdue_invoices' => $delinquency['overdue'],
            ],
            'ticketStats' => $ticketStats,
            'ticketSla' => $ticketSla,
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
            'expiringLeases' => $expiringLeases
        ]);
    }
}

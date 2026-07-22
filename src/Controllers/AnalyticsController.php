<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\Mapping;
use App\Config\App;

class AnalyticsController extends Controller
{
    private $paymentModel;
    private $stallModel;
    private $mappingModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->stallModel = new Stall();
        $this->mappingModel = new Mapping();
    }

    public function index()
    {
        Auth::requireStaffOrAdmin();

        $revenueStats = $this->paymentModel->getRevenueStats();
        $stallsSummary = $this->stallModel->getSummaryStats();
        $monthlyTrends = $this->paymentModel->getMonthlyRevenueTrend();
        $methodBreakdown = $this->paymentModel->getPaymentMethodBreakdown();

        return $this->view('pages/admin/analytics/index', [
            'title' => App::pageTitle('Financial Analytics & Occupancy'),
            'revenueStats' => $revenueStats,
            'stallsSummary' => $stallsSummary,
            'monthlyTrends' => $monthlyTrends,
            'methodBreakdown' => $methodBreakdown
        ]);
    }
}

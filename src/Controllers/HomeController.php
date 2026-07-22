<?php

namespace App\Controllers;

use App\Config\App;
use App\Core\Controller;
use App\Models\Stall;

class HomeController extends Controller {
    public function index() {
        $stallModel = new Stall();
        
        $totalStalls = $stallModel->getTotal();
        $occupiedStalls = $stallModel->getOccupiedCount();
        $availableCount = max(0, $totalStalls - $occupiedStalls);
        $occupancyRate = $totalStalls > 0 ? round(($occupiedStalls / $totalStalls) * 100) : 0;
        
        $availableStalls = $stallModel->getAvailableStalls(6);

        return $this->view('pages/home', [
            'title' => App::pageTitle('Welcome'),
            'availableStalls' => $availableStalls,
            'stats' => [
                'total_stalls' => $totalStalls,
                'occupied_stalls' => $occupiedStalls,
                'available_stalls' => $availableCount,
                'occupancy_rate' => $occupancyRate
            ]
        ]);
    }
}

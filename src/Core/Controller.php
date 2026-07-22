<?php

namespace App\Core;

use App\Config\App;

class Controller {
    protected function view($view, $data = []) {
        $isAdminView = strpos($view, 'pages/admin') !== false;
        $isUserView = strpos($view, 'pages/user') !== false;

        if ($isAdminView) {
            if (!isset($data['upcomingDues'])) {
                $data['upcomingDues'] = (new \App\Models\Payment())->getUpcomingDues();
            }
        } elseif ($isUserView) {
            if (!isset($data['upcomingDues']) && Auth::isLoggedIn()) {
                $data['upcomingDues'] = (new \App\Models\Payment())->getUpcomingDues((int)Auth::user()['id']);
            }
        }

        // Extract variables to be available in the view
        extract($data);
        $basePath = App::basePath();
        $appUrl = App::url('/');
        
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        
        $layoutFile = __DIR__ . '/../Views/layout/main.php'; // Default fallback
        
        if ($isAdminView) {
            $layoutFile = __DIR__ . '/../Views/layout/admin.php';
        } elseif ($isUserView) {
            $layoutFile = __DIR__ . '/../Views/layout/user.php'; // We can use the same or different layout 
        }

        if (file_exists($viewFile)) {
            ob_start();
            require_once $viewFile;
            $content = ob_get_clean();

            if (file_exists($layoutFile)) {
                require_once $layoutFile;
            } else {
                require_once __DIR__ . '/../Views/layout/main.php'; // Ultimate fallback
            }
        } else {
            http_response_code(500);
            error_log("View does not exist: " . $view);
            echo "An internal rendering error occurred.";
        }
    }

    protected function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    protected function redirect($url) {
        $isExternal = preg_match('/^https?:\/\//i', $url);
        $targetUrl = $isExternal ? $url : App::path($url);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->jsonResponse([
                'status' => 'redirect',
                'target' => $targetUrl
            ]);
        }
        header("Location: " . $targetUrl);
        exit();
    }
}

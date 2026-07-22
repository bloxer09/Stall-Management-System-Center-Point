<?php

namespace App\Core;

use App\Config\App;

class Router {
    protected $routes = [];

    public function get($route, $action) {
        $this->addRoute('GET', $route, $action);
    }

    public function post($route, $action) {
        $this->addRoute('POST', $route, $action);
    }

    protected function addRoute($method, $route, $action) {
        // Convert route to regex, and capture variables if any
        $routePattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route);
        $routePattern = "#^" . $routePattern . "$#";
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $routePattern,
            'action' => $action
        ];
    }

    public function dispatch($uri, $method) {
        // Remove query string from URI
        $uri = strtok($uri, '?');
        
        // Remove base path if project is in a subfolder
        $basePath = App::basePath();
        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
            if ($uri === '') $uri = '/';
        }

        // Route-area access guards.
        if (preg_match('#^/admin(?:/|$)#', $uri)) {
            Auth::requireStaffOrAdmin();
        } elseif (preg_match('#^/user(?:/|$)#', $uri)) {
            Auth::requireUser();
        }

        foreach ($this->routes as $route) {
            if ($route['method'] == $method && preg_match($route['pattern'], $uri, $matches)) {
                // Determine controller and method
                $controllerName = $route['action'][0];
                $methodName = $route['action'][1];
                
                $controller = new $controllerName();

                // Extract parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                call_user_func_array([$controller, $methodName], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        $viewFile = __DIR__ . '/../Views/errors/404.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "404 Not Found";
        }
    }
}

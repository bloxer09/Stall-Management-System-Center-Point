<?php

namespace App\Core;

use App\Config\App;

class Session {
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');

            $isHttps = (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                || (string) App::env('SESSION_SECURE_COOKIE', '0') === '1'
            );

            session_name((string) App::env('SESSION_NAME', 'stallm_session'));
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => App::basePath() === '' ? '/' : App::basePath(),
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => (string) App::env('SESSION_SAMESITE', 'Lax'),
            ]);
            session_start();

            if (!isset($_SESSION['_initiated_at'])) {
                session_regenerate_id(true);
                $_SESSION['_initiated_at'] = time();
            }
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    public static function unset($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        $_SESSION = [];

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_unset();
        session_destroy();
        session_write_close();
    }

    public static function regenerate($deleteOld = true) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id((bool) $deleteOld);
        }
    }
    
    // Flash messages
    public static function setFlash($type, $message, ?string $title = null, ?int $duration = null) {
        $type = strtolower(trim((string) $type));
        if (!in_array($type, ['success', 'warning', 'error', 'info'], true)) {
            $type = 'info';
        }

        $_SESSION['flash'] = [
            'type' => $type,
            'title' => $title !== null && trim($title) !== '' ? trim($title) : self::defaultFlashTitle($type),
            'message' => (string) $message,
            'duration' => self::normalizeFlashDuration($duration),
        ];
    }
    
    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function hasFlash(string $type): bool {
        return isset($_SESSION['flash']) && strtolower(trim((string)$_SESSION['flash']['type'])) === strtolower(trim($type));
    }

    public static function flash(string $type): ?string {
        if (self::hasFlash($type)) {
            $message = $_SESSION['flash']['message'];
            unset($_SESSION['flash']);
            return $message;
        }
        return null;
    }

    // CSRF Protection
    public static function csrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    private static function defaultFlashTitle(string $type): string {
        switch ($type) {
            case 'success':
                return 'Success';
            case 'warning':
                return 'Warning';
            case 'error':
                return 'Error';
            default:
                return 'Notice';
        }
    }

    private static function normalizeFlashDuration(?int $duration): int {
        if ($duration === null) {
            return 5000;
        }

        return max(2500, min(15000, $duration));
    }
}

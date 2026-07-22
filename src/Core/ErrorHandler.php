<?php

namespace App\Core;

use App\Config\App;
use Throwable;

class ErrorHandler
{
    private static bool $isHandling = false;
    private static bool $loggingConfigured = false;

    public static function register(): void
    {
        self::configureErrorLogging();

        if (!self::isErrorPageEnabled()) {
            return;
        }

        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $exception): void
    {
        $errorId = self::generateErrorId();
        self::logException($exception, $errorId);
        self::render500($errorId, self::isDebugEnabled() ? $exception->getMessage() : null);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if (!$error || !self::isFatalErrorType((int)$error['type'])) {
            return;
        }

        $message = sprintf(
            '%s in %s on line %d',
            (string)$error['message'],
            (string)$error['file'],
            (int)$error['line']
        );
        $errorId = self::generateErrorId();
        error_log(sprintf('Fatal error [Error ID: %s]: %s', $errorId, $message));

        self::render500($errorId, self::isDebugEnabled() ? $message : null);
    }

    private static function isFatalErrorType(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    }

    private static function isDebugEnabled(): bool
    {
        return self::envFlag('APP_DEBUG', false);
    }

    private static function isErrorPageEnabled(): bool
    {
        return self::envFlag('APP_ERROR_PAGE_ENABLED', true);
    }

    private static function envFlag(string $key, bool $default): bool
    {
        $value = strtolower(trim((string) App::env($key, $default ? '1' : '0')));
        return !in_array($value, ['0', 'false', 'no', 'off', ''], true);
    }

    private static function configureErrorLogging(): void
    {
        if (self::$loggingConfigured) {
            return;
        }

        $logFile = App::storagePath('logs/' . (self::isDebugEnabled() ? 'app-debug.log' : 'app-error.log'));
        $logDirectory = dirname($logFile);

        if (!is_dir($logDirectory) && !@mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            return;
        }

        if (!file_exists($logFile) && @file_put_contents($logFile, '') === false) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('log_errors', '1');
        ini_set('error_log', $logFile);

        self::$loggingConfigured = true;
    }

    private static function render500(string $errorId, ?string $debugDetails = null): void
    {
        $showDebugDetails = self::isDebugEnabled();

        if (self::$isHandling) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo 'An error has occurred. Please try again later.';
            return;
        }

        self::$isHandling = true;

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $userMessage = 'An error has occurred. Please try again later.';
        $viewFile = __DIR__ . '/../Views/errors/500.php';
        if (file_exists($viewFile)) {
            require $viewFile;
            return;
        }

        echo htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8');
        if ($showDebugDetails && !empty($debugDetails)) {
            echo '<pre>' . htmlspecialchars($debugDetails, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }

    private static function logException(Throwable $exception, string $errorId): void
    {
        $message = sprintf(
            'Unhandled exception [Error ID: %s]: %s in %s on line %d',
            $errorId,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        error_log($message);
        error_log($exception->getTraceAsString());
    }

    private static function generateErrorId(): string
    {
        try {
            return date('YmdHis') . '-' . bin2hex(random_bytes(3));
        } catch (Throwable $e) {
            return (string) time();
        }
    }
}

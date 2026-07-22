<?php

namespace App\Config;

class App
{
    public static function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }

    public static function name(): string
    {
        $name = trim((string) self::env('APP_NAME', ''));
        return $name !== '' ? $name : 'APP_NAME';
    }

    public static function pageTitle(string $pageTitle = ''): string
    {
        $pageTitle = trim($pageTitle);
        if ($pageTitle === '') {
            return self::name();
        }

        return $pageTitle . ' - ' . self::name();
    }

    public static function basePath(): string
    {
        $basePath = trim((string) self::env('APP_BASE_PATH', '/stallm'));

        if ($basePath === '' || $basePath === '/') {
            return '';
        }

        $basePath = '/' . trim($basePath, '/');
        return rtrim($basePath, '/');
    }

    public static function url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');

        $configuredUrl = rtrim((string) self::env('APP_URL', ''), '/');
        if ($configuredUrl !== '') {
            $configuredPath = parse_url($configuredUrl, PHP_URL_PATH) ?? '';
            $basePath = self::basePath();

            if ($basePath !== '' && str_ends_with(rtrim($configuredPath, '/'), $basePath)) {
                return $configuredUrl . ($path === '/' ? '' : $path);
            }

            return $configuredUrl . $basePath . ($path === '/' ? '' : $path);
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';

        return $scheme . '://' . $host . self::basePath() . ($path === '/' ? '' : $path);
    }

    public static function path(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        return self::basePath() . ($path === '/' ? '' : $path);
    }

    public static function storagePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
        $path = trim($path);
        if ($path === '') {
            return $base;
        }
        return $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
    }

    public static function projectRoot(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        $path = trim($path);
        if ($path === '') {
            return $base;
        }
        return $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
    }
}

<?php

$runtime = realpath(__DIR__.'/../.runtime');
if (($_ENV['APP_ENV'] ?? null) !== 'testing'
    || ($_ENV['DB_CONNECTION'] ?? null) !== 'sqlite'
    || ($_ENV['DB_DATABASE'] ?? null) !== $runtime.DIRECTORY_SEPARATOR.'e2e.sqlite'
    || realpath($_ENV['LARAVEL_STORAGE_PATH'] ?? '') !== $runtime) {
    http_response_code(503);
    echo 'E2E environment isolation is not configured.';
    exit;
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
if ($uri !== '/' && is_file(getcwd().$uri)) {
    return false;
}

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();
$app['config']->set('filesystems.disks.tmp-for-tests', [
    'driver' => 'local',
    'root' => $runtime.'/app/livewire-browser-uploads',
    'throw' => true,
]);
$app->handleRequest(\Illuminate\Http\Request::capture());

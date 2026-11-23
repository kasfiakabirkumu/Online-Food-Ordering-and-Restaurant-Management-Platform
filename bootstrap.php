<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
$GLOBALS['config'] = require BASE_PATH . '/config/config.php';

spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'controllers'] as $dir) {
        $file = BASE_PATH . "/app/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require BASE_PATH . '/app/core/helpers.php';

set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Error</title><body style="font-family:sans-serif;max-width:640px;margin:3rem auto">';
    echo '<h1>Something went wrong</h1>';
    echo '<p>If this is your first run, make sure MySQL is started and open <a href="setup.php">setup.php</a> once to create the database.</p>';
    if (config('app.debug')) {
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '</body>';
});

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'");

Session::start();

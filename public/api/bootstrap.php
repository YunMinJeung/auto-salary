<?php
// API 엔드포인트 공통 부트스트랩
if (session_status() === PHP_SESSION_NONE) session_start();

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');

require APP_PATH . '/config.php';
require APP_PATH . '/db.php';
require APP_PATH . '/helpers.php';
require APP_PATH . '/Auth.php';

spl_autoload_register(function (string $class): void {
    $paths = [
        APP_PATH . '/models/'      . $class . '.php',
        APP_PATH . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) { require $path; return; }
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

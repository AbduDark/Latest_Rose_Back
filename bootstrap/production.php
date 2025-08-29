
<?php

// Production optimizations
if (function_exists('opcache_compile_file')) {
    opcache_compile_file(__DIR__ . '/../bootstrap/app.php');
}

// Set production environment
if (!defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

// Handle CORS for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

<?php
/**
 * PHP built-in server router
 * Usage: php -S localhost:8000 router.php
 *
 * This file replaces .htaccess for local development.
 * On production (Apache/cPanel), .htaccess handles routing instead.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve real files directly (CSS, JS, images, fonts, etc.)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false; // Let PHP built-in server serve the file as-is
}

// Block sensitive directories (mirrors .htaccess security)
$blocked = ['/config/', '/database/', '/storage/', '/src/', '/views/', '/cron/'];
foreach ($blocked as $b) {
    if (str_starts_with($uri, $b)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// Route everything else through index.php
require __DIR__ . '/index.php';

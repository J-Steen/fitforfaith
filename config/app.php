<?php
/**
 * Application Configuration
 * Copy config/database.example.php to config/database.php and fill in credentials.
 */

define('APP_NAME',    'FitForFaith');
define('APP_TAGLINE', 'Move Together. Raise Together.');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development'); // 'development' or 'production'
define('APP_DEBUG',   APP_ENV === 'development');
define('APP_URL',     rtrim(getenv('APP_URL') ?: 'https://gekbult.co.za', '/'));
define('APP_TIMEZONE','Africa/Johannesburg');
define('BASE_PATH',   dirname(__DIR__));

date_default_timezone_set(APP_TIMEZONE);

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/storage/logs/php_errors.log');
}

// Session security settings (before session_start in Session::start())
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_NAME', 'fff_session');

// Upload / storage paths
define('STORAGE_PATH',   BASE_PATH . '/storage');
define('CACHE_PATH',     STORAGE_PATH . '/cache');
define('QRCODE_PATH',    STORAGE_PATH . '/qrcodes');
define('LOG_PATH',       STORAGE_PATH . '/logs');

// View path
define('VIEW_PATH', BASE_PATH . '/views/');

// Registration fee (in ZAR cents - e.g. 15000 = R150.00)
define('REGISTRATION_FEE_CENTS', 15000);
define('REGISTRATION_FEE_DISPLAY', 'R150.00');
define('CURRENCY', 'ZAR');

// Event dates
define('EVENT_START', '2026-01-01');
define('EVENT_END',   '2026-12-31');

// Points rules (defaults — overridden by settings table)
define('DEFAULT_POINTS_RUN',  10); // per km
define('DEFAULT_POINTS_WALK',  5); // per km
define('DEFAULT_POINTS_RIDE',  3); // per km
define('DEFAULT_MAX_DAILY',  200); // max points per day per user

// Cache TTLs (seconds)
define('CACHE_LEADERBOARD', 300);  // 5 minutes
define('CACHE_SETTINGS',    600);  // 10 minutes
define('CACHE_STATS',       120);  // 2 minutes

// Autoloader — no Composer required
spl_autoload_register(function ($class) {
    // Namespace: App\Core\Database → src/Core/Database.php
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require_once $file;
});

// Global helpers
require_once BASE_PATH . '/src/helpers.php';

// Bootstrap core services
use App\Core\Database;
use App\Core\Session;

Database::init();
Session::start();

// Boot language (reads user preference or session locale)
use App\Core\Lang;
Lang::boot();

// Global error/exception handler
set_exception_handler(function (Throwable $e) {
    $msg = date('Y-m-d H:i:s') . ' [EXCEPTION] ' . $e->getMessage()
         . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n"
         . $e->getTraceAsString() . "\n";
    if (is_dir(LOG_PATH)) {
        file_put_contents(LOG_PATH . '/app.log', $msg, FILE_APPEND | LOCK_EX);
    }
    if (APP_DEBUG) {
        echo '<pre>' . htmlspecialchars($msg) . '</pre>';
    } else {
        http_response_code(500);
        if (file_exists(VIEW_PATH . 'errors/500.php')) include VIEW_PATH . 'errors/500.php';
    }
    exit(1);
});

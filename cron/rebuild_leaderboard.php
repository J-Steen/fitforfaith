<?php
/**
 * Cron: Rebuild leaderboard cache
 * Run every 5 minutes via cPanel cron:
 *   */5 * * * * php /home/username/public_html/cron/rebuild_leaderboard.php >> /dev/null 2>&1
 */
define('BASE_PATH', dirname(__DIR__));
define('CRON_MODE', true);

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/strava.php';
require_once BASE_PATH . '/config/payfast.php';
require_once BASE_PATH . '/config/app.php';

use App\Services\LeaderboardCacheService;

try {
    echo date('Y-m-d H:i:s') . " Starting leaderboard rebuild...\n";
    LeaderboardCacheService::rebuild();
    echo date('Y-m-d H:i:s') . " Done.\n";
} catch (\Throwable $e) {
    echo date('Y-m-d H:i:s') . " ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

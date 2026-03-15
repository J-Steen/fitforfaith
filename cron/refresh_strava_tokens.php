<?php
/**
 * Cron: Proactively refresh Strava tokens expiring within 2 hours
 * Run hourly via cPanel cron:
 *   0 * * * * php /home/username/public_html/cron/refresh_strava_tokens.php >> /dev/null 2>&1
 */
define('BASE_PATH', dirname(__DIR__));
define('CRON_MODE', true);

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/strava.php';
require_once BASE_PATH . '/config/payfast.php';
require_once BASE_PATH . '/config/app.php';

use App\Models\User;
use App\Services\StravaService;

$cutoff = time() + 7200; // tokens expiring within 2 hours
$users  = User::withStravaTokens();
$refreshed = 0;

foreach ($users as $user) {
    if (!$user['strava_token_expires'] || $user['strava_token_expires'] > $cutoff) {
        continue;
    }
    try {
        $tokens = StravaService::refreshToken($user['strava_refresh_token']);
        User::updateStravaTokens(
            (int)$user['id'],
            $tokens['access_token'],
            $tokens['refresh_token'],
            (int)$tokens['expires_at']
        );
        $refreshed++;
        echo "  Refreshed token for user {$user['id']}\n";
    } catch (\Throwable $e) {
        echo "  ERROR refreshing token for user {$user['id']}: " . $e->getMessage() . "\n";
    }
}

echo date('Y-m-d H:i:s') . " Token refresh complete. Refreshed: $refreshed\n";

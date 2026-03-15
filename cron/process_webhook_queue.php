<?php
/**
 * Cron: Process Strava webhook event queue
 * Run every minute via cPanel cron:
 *   * * * * * php /home/username/public_html/cron/process_webhook_queue.php >> /dev/null 2>&1
 */
define('BASE_PATH', dirname(__DIR__));
define('CRON_MODE', true);

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/strava.php';
require_once BASE_PATH . '/config/payfast.php';
require_once BASE_PATH . '/config/app.php';

use App\Models\Webhook;
use App\Models\User;
use App\Models\Activity;
use App\Services\StravaService;
use App\Services\PointsService;
use App\Services\LeaderboardCacheService;

// Reset any stuck processing events
Webhook::resetStuck();

$events = Webhook::getPending(50);
if (empty($events)) {
    exit(0);
}

echo date('Y-m-d H:i:s') . " Processing " . count($events) . " webhook events...\n";

foreach ($events as $event) {
    Webhook::markProcessing($event['id']);

    try {
        $payload = json_decode($event['payload'], true);
        $ownerId  = (int)$event['owner_id'];
        $objectId = (int)$event['object_id'];

        // Find the user by Strava athlete ID
        $user = User::findByStravaId($ownerId);
        if (!$user) {
            echo "  Skipping event {$event['id']}: no user for athlete $ownerId\n";
            Webhook::markDone($event['id']);
            continue;
        }

        $userId   = (int)$user['id'];
        $churchId = $user['church_id'] ? (int)$user['church_id'] : null;

        switch ($event['aspect_type']) {
            case 'create':
            case 'update':
                // Fetch activity from Strava
                $accessToken = StravaService::ensureFreshToken($userId);
                $act         = StravaService::getActivity($accessToken, $objectId);

                // Skip if not a scored activity type
                $scoredTypes = ['Run', 'Walk', 'Ride', 'VirtualRide', 'Hike'];
                if (!in_array($act['type'] ?? '', $scoredTypes, true)) {
                    echo "  Skipping non-scored activity type: " . ($act['type'] ?? 'unknown') . "\n";
                    Webhook::markDone($event['id']);
                    break;
                }

                $points = PointsService::calculateFinal(
                    $userId,
                    $act['type'],
                    (float)($act['distance'] ?? 0),
                    $act['start_date_local'] ?? $act['start_date'] ?? date('Y-m-d H:i:s')
                );

                Activity::upsert([
                    'user_id'         => $userId,
                    'church_id'       => $churchId,
                    'strava_id'       => $objectId,
                    'activity_type'   => $act['type'],
                    'name'            => $act['name'] ?? null,
                    'distance_meters' => (float)($act['distance'] ?? 0),
                    'moving_time_sec' => (int)($act['moving_time'] ?? 0),
                    'start_date'      => date('Y-m-d H:i:s', strtotime($act['start_date_local'] ?? $act['start_date'] ?? 'now')),
                    'points_awarded'  => $points,
                    'raw_payload'     => json_encode($act),
                ]);

                // Quick user cache rebuild
                LeaderboardCacheService::rebuildUser($userId);

                echo "  Processed activity $objectId for user $userId: $points points\n";
                break;

            case 'delete':
                Activity::deleteByStravaId($objectId);
                LeaderboardCacheService::rebuildUser($userId);
                echo "  Deleted activity $objectId for user $userId\n";
                break;
        }

        Webhook::markDone($event['id']);

    } catch (\Throwable $e) {
        echo "  ERROR event {$event['id']}: " . $e->getMessage() . "\n";
        Webhook::markFailed($event['id'], $e->getMessage());
    }
}

echo date('Y-m-d H:i:s') . " Webhook processing complete.\n";

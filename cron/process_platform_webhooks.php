<?php
/**
 * FitForFaith — Platform Webhook / Backfill Processor
 *
 * Processes pending jobs in platform_webhook_queue.
 * Run via cron every minute:
 *   * * * * * php /path/to/cron/process_platform_webhooks.php >> /tmp/platform_webhooks.log 2>&1
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/fitbit.php';
require_once __DIR__ . '/../config/garmin.php';
require_once __DIR__ . '/../config/polar.php';
require_once __DIR__ . '/../config/wahoo.php';
require_once __DIR__ . '/../config/suunto.php';

use App\Models\PlatformToken;
use App\Models\PlatformWebhook;
use App\Models\PlatformActivity;
use App\Models\User;
use App\Services\PointsService;
use App\Services\LeaderboardCacheService;
use App\Services\FitbitService;
use App\Services\GarminService;
use App\Services\PolarService;
use App\Services\WahooService;
use App\Services\SuuntoService;

define('BATCH_SIZE', 10);

// Reset any jobs stuck in "processing" for more than 5 minutes
PlatformWebhook::resetStuck();

$jobs = PlatformWebhook::getPending(BATCH_SIZE);

if (empty($jobs)) {
    exit(0); // Nothing to do
}

app_log('Processing ' . count($jobs) . ' platform webhook job(s)', 'INFO');

foreach ($jobs as $job) {
    PlatformWebhook::markProcessing((int)$job['id']);
    try {
        processJob($job);
        PlatformWebhook::markDone((int)$job['id']);
    } catch (\Throwable $e) {
        app_log("Webhook job #{$job['id']} ({$job['platform']}/{$job['event_type']}) failed: " . $e->getMessage(), 'ERROR');
        PlatformWebhook::markFailed((int)$job['id'], $e->getMessage());
    }
}

// ── Main job processor ─────────────────────────────────────────

function processJob(array $job): void {
    $platform   = $job['platform'];
    $platformUid = (string)($job['platform_uid'] ?? '');
    $externalId  = (string)($job['external_id'] ?? '');
    $eventType   = $job['event_type'];
    $payload     = $job['payload'] ?? null;

    // Resolve user from platform UID
    $userId = PlatformToken::resolveUser($platform, $platformUid);
    if (!$userId) {
        throw new \RuntimeException("No user found for platform={$platform} uid={$platformUid}");
    }

    $user = User::findById($userId);
    if (!$user) {
        throw new \RuntimeException("User {$userId} not found in database");
    }

    // Handle delete event
    if ($eventType === 'delete' && $externalId) {
        PlatformActivity::deleteByExternalId($platform, $externalId);
        LeaderboardCacheService::rebuildUser($userId);
        app_log("Deleted {$platform} activity ext={$externalId} for user={$userId}", 'INFO');
        return;
    }

    // Get stored token
    $token = PlatformToken::find($userId, $platform);
    if (!$token) {
        throw new \RuntimeException("No token for user={$userId} platform={$platform}");
    }

    // Refresh token if needed
    $token = ensureFreshToken($platform, $userId, $token);

    // Fetch raw activities from the platform
    $rawActivities = fetchActivities($platform, $token, $eventType, $externalId, $payload);

    $imported = 0;
    foreach ($rawActivities as $raw) {
        $normalized = normalizeActivity($platform, $raw);
        if (!$normalized) continue;

        $points = PointsService::calculateFinal(
            $userId,
            $normalized['activity_type'],
            $normalized['distance_meters'],
            $normalized['start_date']
        );

        PlatformActivity::upsert([
            'user_id'         => $userId,
            'church_id'       => $user['church_id'] ?? null,
            'platform'        => $platform,
            'external_id'     => $normalized['external_id'],
            'activity_type'   => $normalized['activity_type'],
            'name'            => $normalized['name'],
            'distance_meters' => $normalized['distance_meters'],
            'moving_time_sec' => $normalized['moving_time_sec'],
            'start_date'      => $normalized['start_date'],
            'points_awarded'  => $points,
            'raw_payload'     => json_encode($raw),
        ]);
        $imported++;
    }

    LeaderboardCacheService::rebuildUser($userId);
    app_log("Job #{$job['id']}: {$platform}/{$eventType} — {$imported} activities for user={$userId}", 'INFO');
}

// ── Token refresh ──────────────────────────────────────────────

function ensureFreshToken(string $platform, int $userId, array $token): array {
    if (!PlatformToken::needsRefresh($token)) return $token;

    $refreshToken = $token['refresh_token'];
    if (!$refreshToken) return $token; // can't refresh without it

    $new = match ($platform) {
        'fitbit' => FitbitService::refreshToken($refreshToken),
        'wahoo'  => WahooService::refreshToken($refreshToken),
        'suunto' => SuuntoService::refreshToken($refreshToken),
        'garmin' => null,  // Garmin OAuth 1.0a tokens don't expire
        'polar'  => null,  // Polar tokens are long-lived; re-auth needed if expired
        default  => null,
    };

    if ($new) {
        PlatformToken::updateTokens(
            $userId, $platform,
            $new['access_token'],
            $new['refresh_token'] ?? null,
            $new['token_expires']  ?? null
        );
        $token['access_token']  = $new['access_token'];
        $token['refresh_token'] = $new['refresh_token'] ?? $token['refresh_token'];
        $token['token_expires'] = $new['token_expires']  ?? $token['token_expires'];
        app_log("Refreshed {$platform} token for user={$userId}", 'INFO');
    }

    return $token;
}

// ── Fetch raw activities per platform ─────────────────────────

function fetchActivities(string $platform, array $token, string $eventType, string $externalId, ?string $payload): array {
    return match ($platform) {
        'fitbit' => fetchFitbit($token['access_token'], $eventType, $payload),
        'garmin' => fetchGarmin($token, $eventType, $payload),
        'polar'  => fetchPolar($token['access_token'], (string)($token['platform_user_id'] ?? ''), $eventType),
        'wahoo'  => fetchWahoo($token['access_token'], $eventType, $payload),
        'suunto' => fetchSuunto($token['access_token'], $eventType, $payload),
        default  => [],
    };
}

function fetchFitbit(string $accessToken, string $eventType, ?string $payload): array {
    if ($eventType === 'backfill') {
        return FitbitService::fetchActivitiesSince($accessToken, date('Y-m-d', strtotime('-90 days')));
    }
    // Webhook notifies by date; re-fetch that day
    if ($payload) {
        $evt  = json_decode($payload, true) ?? [];
        $date = $evt['date'] ?? date('Y-m-d', strtotime('-1 day'));
        return FitbitService::fetchActivitiesSince($accessToken, $date);
    }
    return [];
}

function fetchGarmin(array $token, string $eventType, ?string $payload): array {
    $accessToken = $token['access_token'];
    $tokenSecret = $token['refresh_token']; // Garmin stores OAuth token secret in refresh_token field

    if ($eventType === 'backfill') {
        $since = time() - (90 * 86400);
        return GarminService::fetchActivities($accessToken, $tokenSecret, $since);
    }
    // Webhook pushes activity data directly in the payload
    if ($payload) {
        $data = json_decode($payload, true) ?? [];
        return $data['activityFiles'] ?? $data['activities'] ?? [];
    }
    return [];
}

function fetchPolar(string $accessToken, string $platformUid, string $eventType): array {
    // Polar uses transaction-based pull; always fetch pending exercises
    return PolarService::getExercises($accessToken, $platformUid);
}

function fetchWahoo(string $accessToken, string $eventType, ?string $payload): array {
    if ($eventType === 'backfill') {
        $all = [];
        for ($page = 1; $page <= 5; $page++) {
            $batch = WahooService::fetchWorkouts($accessToken, $page);
            if (empty($batch)) break;
            $all = array_merge($all, $batch);
        }
        return $all;
    }
    // Webhook delivers workout payload directly
    if ($payload) {
        $data = json_decode($payload, true) ?? [];
        $w    = $data['workout'] ?? null;
        return $w ? [$w] : [];
    }
    return [];
}

function fetchSuunto(string $accessToken, string $eventType, ?string $payload): array {
    if ($eventType === 'backfill') {
        $since = date('c', strtotime('-90 days'));
        return SuuntoService::fetchWorkouts($accessToken, $since);
    }
    // For create events, re-fetch recent workouts (Suunto webhook only gives workoutid)
    return SuuntoService::fetchWorkouts($accessToken);
}

// ── Normalize raw activity to standard format ──────────────────

function normalizeActivity(string $platform, array $raw): ?array {
    return match ($platform) {
        'fitbit' => FitbitService::normalizeActivity($raw),
        'garmin' => GarminService::normalizeActivity($raw),
        'polar'  => PolarService::normalizeActivity($raw),
        'wahoo'  => WahooService::normalizeActivity($raw),
        'suunto' => SuuntoService::normalizeActivity($raw),
        default  => null,
    };
}

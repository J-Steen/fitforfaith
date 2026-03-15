<?php
namespace App\Services;

class PolarService extends BasePlatformService {
    const PLATFORM = 'polar';

    public static function getAuthUrl(string $state): string {
        return POLAR_AUTH_URL . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => POLAR_CLIENT_ID,
            'redirect_uri'  => POLAR_REDIRECT_URI,
            'scope'         => POLAR_SCOPE,
            'state'         => $state,
        ]);
    }

    public static function exchangeCode(string $code): array {
        $auth = 'Basic ' . base64_encode(POLAR_CLIENT_ID . ':' . POLAR_CLIENT_SECRET);
        $resp = self::httpPost(POLAR_TOKEN_URL, [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => POLAR_REDIRECT_URI,
        ], ['Authorization: ' . $auth]);

        if (empty($resp['access_token'])) throw new \RuntimeException('Polar token exchange failed: ' . json_encode($resp));
        return [
            'access_token'     => $resp['access_token'],
            'refresh_token'    => $resp['refresh_token'] ?? null,
            'token_expires'    => isset($resp['expires_in']) ? time() + $resp['expires_in'] : null,
            'platform_user_id' => (string)($resp['x_user_id'] ?? ''),
        ];
    }

    /** Register the user with Polar Accesslink (must be called once after OAuth). */
    public static function registerUser(string $accessToken, string $polarUserId): void {
        try {
            self::httpPostJson(POLAR_API_BASE . '/users', ['member-id' => $polarUserId], $accessToken);
        } catch (\Throwable $e) {
            // 409 = user already registered — safe to ignore
            if (!str_contains($e->getMessage(), '409')) throw $e;
        }
    }

    /** Create an exercise transaction and return list of exercises. */
    public static function getExercises(string $accessToken, string $polarUserId): array {
        // 1. Create transaction
        $txResp = self::httpPostJson(
            POLAR_API_BASE . "/users/{$polarUserId}/exercise-transactions",
            [],
            $accessToken
        );
        if (empty($txResp['transaction-id'])) return [];
        $txId = $txResp['transaction-id'];

        // 2. List exercises in transaction
        $list = self::httpGet(
            POLAR_API_BASE . "/users/{$polarUserId}/exercise-transactions/{$txId}",
            $accessToken
        );
        $exercises = [];
        foreach ($list['exercises'] ?? [] as $url) {
            try {
                $ex = self::httpGet($url, $accessToken);
                if ($ex) $exercises[] = $ex;
            } catch (\Throwable $e) {
                // skip bad entries
            }
        }

        // 3. Commit transaction
        try {
            $ch = curl_init(POLAR_API_BASE . "/users/{$polarUserId}/exercise-transactions/{$txId}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'PUT',
                CURLOPT_HTTPHEADER     => ["Authorization: Bearer $accessToken", 'Content-Length: 0'],
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            @curl_close($ch);
        } catch (\Throwable $e) {}

        return $exercises;
    }

    public static function normalizeType(string $rawType): ?string {
        return match(strtoupper(trim($rawType))) {
            'RUNNING','TRAIL_RUNNING','TREADMILL_RUNNING','TRACK_AND_FIELD' => 'Run',
            'WALKING','HIKING','ORIENTEERING'                                => 'Walk',
            'CYCLING','INDOOR_CYCLING','MOUNTAIN_BIKING','ROAD_CYCLING'     => 'Ride',
            default => null,
        };
    }

    public static function normalizeActivity(array $raw): ?array {
        $type = self::normalizeType($raw['sport'] ?? $raw['detailed-sport-info'] ?? '');
        if (!$type) return null;

        $distM   = (float)($raw['distance'] ?? 0);
        $durStr  = $raw['duration'] ?? 'PT0S';  // ISO 8601 duration
        $durSec  = self::parseIsoDuration($durStr);
        $startDt = date('Y-m-d H:i:s', strtotime($raw['start-time'] ?? 'now'));
        $extId   = basename($raw['id'] ?? '') ?: (string)crc32(json_encode($raw));

        return [
            'external_id'     => $extId,
            'activity_type'   => $type,
            'name'            => $raw['sport'] ?? $type,
            'distance_meters' => $distM,
            'moving_time_sec' => $durSec,
            'start_date'      => $startDt,
            'raw'             => $raw,
        ];
    }

    private static function parseIsoDuration(string $iso): int {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $iso, $m);
        return (int)($m[1] ?? 0) * 3600 + (int)($m[2] ?? 0) * 60 + (int)($m[3] ?? 0);
    }
}

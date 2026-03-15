<?php
namespace App\Services;

class FitbitService extends BasePlatformService {
    const PLATFORM = 'fitbit';

    private static function basicAuth(): string {
        return 'Basic ' . base64_encode(FITBIT_CLIENT_ID . ':' . FITBIT_CLIENT_SECRET);
    }

    public static function getAuthUrl(string $state): string {
        return FITBIT_AUTH_URL . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => FITBIT_CLIENT_ID,
            'redirect_uri'  => FITBIT_REDIRECT_URI,
            'scope'         => FITBIT_SCOPE,
            'state'         => $state,
        ]);
    }

    public static function exchangeCode(string $code): array {
        $resp = self::httpPost(FITBIT_TOKEN_URL, [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => FITBIT_REDIRECT_URI,
        ], ['Authorization: ' . self::basicAuth()]);

        if (empty($resp['access_token'])) throw new \RuntimeException('Fitbit token exchange failed: ' . json_encode($resp));
        return [
            'access_token'     => $resp['access_token'],
            'refresh_token'    => $resp['refresh_token'] ?? null,
            'token_expires'    => time() + ($resp['expires_in'] ?? 28800),
            'platform_user_id' => $resp['user_id'] ?? null,
        ];
    }

    public static function refreshToken(string $refreshToken): array {
        $resp = self::httpPost(FITBIT_TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ], ['Authorization: ' . self::basicAuth()]);

        if (empty($resp['access_token'])) throw new \RuntimeException('Fitbit token refresh failed.');
        return [
            'access_token'  => $resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? $refreshToken,
            'token_expires' => time() + ($resp['expires_in'] ?? 28800),
        ];
    }

    /** Fetch activities from Fitbit after a given date (Y-m-d). Returns raw array. */
    public static function fetchActivitiesSince(string $accessToken, string $afterDate = '2025-01-01'): array {
        $all    = [];
        $offset = 0;
        do {
            $resp = self::httpGet(
                FITBIT_API_BASE . '/1/user/-/activities/list.json',
                $accessToken,
                ['afterDate' => $afterDate, 'offset' => $offset, 'limit' => 100, 'sort' => 'asc'],
                ['Accept-Language: nl_NL']   // metric units (km)
            );
            $batch  = $resp['activities'] ?? [];
            $all    = array_merge($all, $batch);
            $offset += count($batch);
            $hasMore = !empty($resp['pagination']['next']);
        } while ($hasMore && count($batch) === 100);
        return $all;
    }

    /** Subscribe to Fitbit activity notifications for the connected user. */
    public static function subscribe(string $accessToken, string $subscriptionId): void {
        $url = FITBIT_API_BASE . "/1/user/-/activities/apiSubscriptions/{$subscriptionId}.json";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $accessToken"],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        @curl_close($ch);
    }

    public static function normalizeType(string $rawType): ?string {
        return match(strtolower(trim($rawType))) {
            'run','running','jogging','treadmill','trail run','track run','outdoor run' => 'Run',
            'walk','walking','outdoor walk','treadmill walk','hike','hiking'            => 'Walk',
            'bike','bicycle','cycling','road bike','mountain bike','indoor cycling',
            'spinning','stationary bike'                                                => 'Ride',
            default => null,
        };
    }

    public static function normalizeActivity(array $raw): ?array {
        $type = self::normalizeType($raw['activityName'] ?? '');
        if (!$type) return null;

        // Fitbit returns distance in km when Accept-Language is metric
        $km      = (float)($raw['distance'] ?? 0);
        $distM   = $km * 1000;
        $durSec  = (int)(($raw['duration'] ?? 0) / 1000);
        $startTs = strtotime($raw['startTime'] ?? 'now');

        return [
            'external_id'     => (string)($raw['logId'] ?? ''),
            'activity_type'   => $type,
            'name'            => $raw['activityName'] ?? $type,
            'distance_meters' => $distM,
            'moving_time_sec' => $durSec,
            'start_date'      => date('Y-m-d H:i:s', $startTs),
            'raw'             => $raw,
        ];
    }
}

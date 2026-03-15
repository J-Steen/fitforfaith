<?php
namespace App\Services;

class WahooService extends BasePlatformService {
    const PLATFORM = 'wahoo';

    public static function getAuthUrl(string $state): string {
        return WAHOO_AUTH_URL . '?' . http_build_query([
            'client_id'     => WAHOO_CLIENT_ID,
            'redirect_uri'  => WAHOO_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => WAHOO_SCOPE,
            'state'         => $state,
        ]);
    }

    public static function exchangeCode(string $code): array {
        $resp = self::httpPost(WAHOO_TOKEN_URL, [
            'client_id'     => WAHOO_CLIENT_ID,
            'client_secret' => WAHOO_CLIENT_SECRET,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => WAHOO_REDIRECT_URI,
        ]);
        if (empty($resp['access_token'])) throw new \RuntimeException('Wahoo token exchange failed: ' . json_encode($resp));
        return [
            'access_token'     => $resp['access_token'],
            'refresh_token'    => $resp['refresh_token'] ?? null,
            'token_expires'    => isset($resp['expires_in']) ? time() + $resp['expires_in'] : null,
            'platform_user_id' => null,  // fetched via /user endpoint after connect
        ];
    }

    public static function refreshToken(string $refreshToken): array {
        $resp = self::httpPost(WAHOO_TOKEN_URL, [
            'client_id'     => WAHOO_CLIENT_ID,
            'client_secret' => WAHOO_CLIENT_SECRET,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
        if (empty($resp['access_token'])) throw new \RuntimeException('Wahoo token refresh failed.');
        return [
            'access_token'  => $resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? $refreshToken,
            'token_expires' => isset($resp['expires_in']) ? time() + $resp['expires_in'] : null,
        ];
    }

    public static function getUser(string $accessToken): array {
        return self::httpGet(WAHOO_API_BASE . '/user', $accessToken);
    }

    public static function fetchWorkouts(string $accessToken, int $page = 1): array {
        $resp = self::httpGet(WAHOO_API_BASE . '/workouts', $accessToken, ['page' => $page, 'per_page' => 100]);
        return $resp['workouts'] ?? [];
    }

    public static function normalizeType(string $rawType): ?string {
        $t = strtolower(trim($rawType));
        if (str_contains($t, 'run') || str_contains($t, 'jogg'))          return 'Run';
        if (str_contains($t, 'walk') || str_contains($t, 'hike'))         return 'Walk';
        if (str_contains($t, 'cycl') || str_contains($t, 'bike') || str_contains($t, 'bik') || str_contains($t, 'spin')) return 'Ride';
        return null;
    }

    public static function normalizeActivity(array $raw): ?array {
        // Wahoo workouts have a nested workout_type
        $typeName = $raw['workout_type']['name'] ?? $raw['name'] ?? '';
        $type = self::normalizeType($typeName);
        if (!$type) return null;

        $distM  = (float)($raw['minutes_moving'] ?? 0) > 0
            ? (float)($raw['distance_meters'] ?? $raw['distance'] ?? 0)
            : 0;
        $durSec = (int)(($raw['minutes_elapsed'] ?? 0) * 60);
        $start  = date('Y-m-d H:i:s', strtotime($raw['created_at'] ?? 'now'));

        return [
            'external_id'     => (string)($raw['id'] ?? ''),
            'activity_type'   => $type,
            'name'            => $typeName ?: $type,
            'distance_meters' => $distM,
            'moving_time_sec' => $durSec,
            'start_date'      => $start,
            'raw'             => $raw,
        ];
    }
}

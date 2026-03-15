<?php
namespace App\Services;

class SuuntoService extends BasePlatformService {
    const PLATFORM = 'suunto';

    public static function getAuthUrl(string $state): string {
        return SUUNTO_AUTH_URL . '?' . http_build_query([
            'client_id'     => SUUNTO_CLIENT_ID,
            'redirect_uri'  => SUUNTO_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => SUUNTO_SCOPE,
            'state'         => $state,
        ]);
    }

    public static function exchangeCode(string $code): array {
        $resp = self::httpPost(SUUNTO_TOKEN_URL, [
            'client_id'     => SUUNTO_CLIENT_ID,
            'client_secret' => SUUNTO_CLIENT_SECRET,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => SUUNTO_REDIRECT_URI,
        ]);
        if (empty($resp['access_token'])) throw new \RuntimeException('Suunto token exchange failed: ' . json_encode($resp));
        return [
            'access_token'     => $resp['access_token'],
            'refresh_token'    => $resp['refresh_token'] ?? null,
            'token_expires'    => isset($resp['expires_in']) ? time() + $resp['expires_in'] : null,
            'platform_user_id' => $resp['user'] ?? null,
        ];
    }

    public static function refreshToken(string $refreshToken): array {
        $resp = self::httpPost(SUUNTO_TOKEN_URL, [
            'client_id'     => SUUNTO_CLIENT_ID,
            'client_secret' => SUUNTO_CLIENT_SECRET,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
        if (empty($resp['access_token'])) throw new \RuntimeException('Suunto token refresh failed.');
        return [
            'access_token'  => $resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? $refreshToken,
            'token_expires' => isset($resp['expires_in']) ? time() + $resp['expires_in'] : null,
        ];
    }

    public static function fetchWorkouts(string $accessToken, ?string $since = null): array {
        $params = ['limit' => 100, 'order' => 'desc'];
        if ($since) $params['since'] = $since;
        $resp = self::httpGet(SUUNTO_API_BASE . '/workouts', $accessToken, $params,
            ['Ocp-Apim-Subscription-Key: ' . SUUNTO_SUBSCRIPTION_KEY]);
        return $resp['payload'] ?? [];
    }

    public static function normalizeType(string $rawType): ?string {
        return match(strtolower(trim($rawType))) {
            'running','trail running','treadmill running','fell running' => 'Run',
            'walking','hiking','nordic walking'                          => 'Walk',
            'cycling','mountain biking','road cycling','indoor cycling',
            'virtual cycling'                                            => 'Ride',
            default => null,
        };
    }

    public static function normalizeActivity(array $raw): ?array {
        $type = self::normalizeType($raw['activityId'] ?? $raw['activity'] ?? '');
        if (!$type) return null;

        $distM  = (float)($raw['totalDistance'] ?? 0);
        $durSec = (int)($raw['totalTime'] ?? 0);
        $start  = date('Y-m-d H:i:s', strtotime($raw['startTime'] ?? 'now'));

        return [
            'external_id'     => (string)($raw['workoutKey'] ?? $raw['id'] ?? ''),
            'activity_type'   => $type,
            'name'            => ucfirst(strtolower($raw['activityId'] ?? $type)),
            'distance_meters' => $distM,
            'moving_time_sec' => $durSec,
            'start_date'      => $start,
            'raw'             => $raw,
        ];
    }
}

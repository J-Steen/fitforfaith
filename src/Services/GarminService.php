<?php
namespace App\Services;

/**
 * Garmin Health API — uses OAuth 1.0a (not 2.0).
 * Requires partner approval: https://developer.garmin.com/gc-developer-program/overview/
 */
class GarminService extends BasePlatformService {
    const PLATFORM = 'garmin';

    // ── OAuth 1.0a ──────────────────────────────────────────────

    private static function oauth1Header(string $method, string $url, array $extraParams, string $tokenSecret = ''): string {
        $nonce     = bin2hex(random_bytes(16));
        $timestamp = time();
        $params    = array_merge($extraParams, [
            'oauth_consumer_key'     => GARMIN_CONSUMER_KEY,
            'oauth_nonce'            => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => $timestamp,
            'oauth_version'          => '1.0',
        ]);
        ksort($params);
        $paramStr  = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $baseStr   = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($paramStr);
        $sigKey    = rawurlencode(GARMIN_CONSUMER_SECRET) . '&' . rawurlencode($tokenSecret);
        $sig       = base64_encode(hash_hmac('sha1', $baseStr, $sigKey, true));
        $params['oauth_signature'] = $sig;

        $parts = [];
        foreach ($params as $k => $v) {
            if (str_starts_with($k, 'oauth_')) {
                $parts[] = rawurlencode($k) . '="' . rawurlencode($v) . '"';
            }
        }
        return 'OAuth ' . implode(', ', $parts);
    }

    /** Step 1: Get a request token. Returns ['oauth_token', 'oauth_token_secret']. */
    public static function getRequestToken(): array {
        $url  = GARMIN_REQUEST_TOKEN_URL;
        $auth = self::oauth1Header('POST', $url, ['oauth_callback' => rawurlencode(GARMIN_CALLBACK_URL)]);
        $ch   = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => ["Authorization: $auth"],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        @curl_close($ch);
        parse_str($body, $result);
        if (empty($result['oauth_token'])) throw new \RuntimeException('Garmin request token failed: ' . $body);
        return $result;
    }

    public static function getAuthUrl(string $oauthToken): string {
        return GARMIN_AUTH_URL . '?oauth_token=' . rawurlencode($oauthToken);
    }

    /** Step 3: Exchange oauth_token + oauth_verifier for access token. */
    public static function exchangeToken(string $oauthToken, string $oauthTokenSecret, string $oauthVerifier): array {
        $url    = GARMIN_ACCESS_TOKEN_URL;
        $params = ['oauth_token' => $oauthToken, 'oauth_verifier' => $oauthVerifier];
        $auth   = self::oauth1Header('POST', $url, $params, $oauthTokenSecret);
        $ch     = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['oauth_verifier' => $oauthVerifier]),
            CURLOPT_HTTPHEADER     => ["Authorization: $auth"],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        @curl_close($ch);
        parse_str($body, $result);
        if (empty($result['oauth_token'])) throw new \RuntimeException('Garmin access token failed: ' . $body);
        return [
            'access_token'     => $result['oauth_token'],
            'refresh_token'    => $result['oauth_token_secret'],  // stored in refresh_token field
            'token_expires'    => null,                            // Garmin tokens don't expire
            'platform_user_id' => $result['oauth_token'],         // Garmin userId fetched separately
        ];
    }

    /** Fetch recent activities. $accessToken = oauth token, $tokenSecret = oauth token secret. */
    public static function fetchActivities(string $accessToken, string $tokenSecret, int $uploadStartTime = 0): array {
        $url    = GARMIN_API_BASE . '/wellness-api/rest/activities';
        $params = ['uploadStartTimeInSeconds' => $uploadStartTime, 'uploadEndTimeInSeconds' => time()];
        $auth   = self::oauth1Header('GET', $url, array_merge($params, ['oauth_token' => $accessToken]), $tokenSecret);
        $fullUrl = $url . '?' . http_build_query($params);

        $ch = curl_init($fullUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: $auth", 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);

        if ($code >= 400) throw new \RuntimeException("Garmin API error $code: $body");
        $data = json_decode($body, true) ?? [];
        return $data['activityFiles'] ?? $data['activities'] ?? [];
    }

    public static function normalizeType(string $rawType): ?string {
        return match(strtoupper(trim($rawType))) {
            'RUNNING','TRACK_RUNNING','TREADMILL_RUNNING','TRAIL_RUNNING','INDOOR_RUNNING' => 'Run',
            'WALKING','CASUAL_WALKING','SPEED_WALKING','INDOOR_WALKING','HIKING'           => 'Walk',
            'CYCLING','MOUNTAIN_BIKING','ROAD_BIKING','INDOOR_CYCLING','VIRTUAL_RIDE',
            'GRAVEL_CYCLING','BMX','TRACK_CYCLING'                                         => 'Ride',
            default => null,
        };
    }

    public static function normalizeActivity(array $raw): ?array {
        $typeKey = $raw['activityType'] ?? $raw['sport'] ?? '';
        $type    = self::normalizeType($typeKey);
        if (!$type) return null;

        $distM  = (float)($raw['distanceInMeters'] ?? $raw['distance'] ?? 0);
        $durSec = (int)($raw['durationInSeconds'] ?? $raw['duration'] ?? 0);
        $start  = $raw['startTimeInSeconds'] ?? $raw['startTimeGMT'] ?? null;
        $startDt = $start ? date('Y-m-d H:i:s', is_numeric($start) ? (int)$start : strtotime($start)) : date('Y-m-d H:i:s');

        return [
            'external_id'     => (string)($raw['activityId'] ?? $raw['summaryId'] ?? uniqid()),
            'activity_type'   => $type,
            'name'            => $raw['activityName'] ?? $type,
            'distance_meters' => $distM,
            'moving_time_sec' => $durSec,
            'start_date'      => $startDt,
            'raw'             => $raw,
        ];
    }
}

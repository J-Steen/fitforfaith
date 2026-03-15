<?php
namespace App\Services;

use App\Models\User;

class StravaService {
    /**
     * Build the Strava OAuth authorization URL.
     */
    public static function getAuthUrl(string $state): string {
        $params = http_build_query([
            'client_id'     => STRAVA_CLIENT_ID,
            'redirect_uri'  => STRAVA_REDIRECT_URI,
            'response_type' => 'code',
            'approval_prompt'=> 'auto',
            'scope'         => STRAVA_SCOPE,
            'state'         => $state,
        ]);
        return STRAVA_AUTH_URL . '?' . $params;
    }

    /**
     * Exchange an authorization code for tokens.
     */
    public static function exchangeCode(string $code): array {
        $response = self::post(STRAVA_TOKEN_URL, [
            'client_id'     => STRAVA_CLIENT_ID,
            'client_secret' => STRAVA_CLIENT_SECRET,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);
        if (!isset($response['access_token'])) {
            throw new \RuntimeException('Strava token exchange failed: ' . json_encode($response));
        }
        return $response;
    }

    /**
     * Refresh an expired access token.
     */
    public static function refreshToken(string $refreshToken): array {
        $response = self::post(STRAVA_TOKEN_URL, [
            'client_id'     => STRAVA_CLIENT_ID,
            'client_secret' => STRAVA_CLIENT_SECRET,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
        if (!isset($response['access_token'])) {
            throw new \RuntimeException('Strava token refresh failed: ' . json_encode($response));
        }
        return $response;
    }

    /**
     * Ensure a user has a fresh Strava access token.
     * Returns the valid access token string.
     */
    public static function ensureFreshToken(int $userId): string {
        $user = User::findById($userId);
        if (!$user || !$user['strava_athlete_id']) {
            throw new \RuntimeException('User has no Strava connection.');
        }

        // Refresh if token expires within 5 minutes
        if ($user['strava_token_expires'] < (time() + 300)) {
            $tokens = self::refreshToken($user['strava_refresh_token']);
            User::updateStravaTokens(
                $userId,
                $tokens['access_token'],
                $tokens['refresh_token'],
                (int)$tokens['expires_at']
            );
            return $tokens['access_token'];
        }

        return $user['strava_access_token'];
    }

    /**
     * Fetch a page of activities for a user.
     */
    public static function getActivities(string $accessToken, ?int $after = null, ?int $before = null, int $page = 1): array {
        $params = ['per_page' => 50, 'page' => $page];
        if ($after)  $params['after']  = $after;
        if ($before) $params['before'] = $before;

        return self::get(STRAVA_API_BASE . '/athlete/activities', $accessToken, $params);
    }

    /**
     * Fetch a single activity by ID.
     */
    public static function getActivity(string $accessToken, int $activityId): array {
        return self::get(STRAVA_API_BASE . '/activities/' . $activityId, $accessToken);
    }

    /**
     * Fetch the athlete profile.
     */
    public static function getAthlete(string $accessToken): array {
        return self::get(STRAVA_API_BASE . '/athlete', $accessToken);
    }

    /**
     * Verify the Strava webhook challenge.
     */
    public static function verifyWebhookChallenge(string $mode, string $challenge, string $verifyToken): bool {
        return $mode === 'subscribe' && $verifyToken === STRAVA_VERIFY_TOKEN;
    }

    /**
     * Fetch all activities since a timestamp (paginated, handles multiple pages).
     * Returns array of activities.
     */
    public static function fetchAllActivitiesSince(string $accessToken, int $after): array {
        $all  = [];
        $page = 1;
        do {
            $batch = self::getActivities($accessToken, $after, null, $page);
            $all   = array_merge($all, $batch);
            $page++;
        } while (count($batch) === 50);
        return $all;
    }

    // ---------------------------------------------------------------
    // HTTP helpers
    // ---------------------------------------------------------------

    private static function get(string $url, string $accessToken, array $params = []): array {
        if ($params) $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $accessToken"],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);

        if ($body === false) throw new \RuntimeException('Strava API request failed (network error).');

        $data = json_decode($body, true);
        if ($code >= 400) {
            throw new \RuntimeException('Strava API error ' . $code . ': ' . ($data['message'] ?? $body));
        }
        return $data ?? [];
    }

    private static function post(string $url, array $data): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        @curl_close($ch);

        if ($body === false) throw new \RuntimeException('Strava token request failed (network error).');

        return json_decode($body, true) ?? [];
    }
}

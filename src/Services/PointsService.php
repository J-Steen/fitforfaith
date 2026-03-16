<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Cache;

class PointsService {
    private static ?array $rates = null;

    private static function getRates(): array {
        if (self::$rates !== null) return self::$rates;

        self::$rates = Cache::remember('points_rates', CACHE_SETTINGS, function () {
            $rows = Database::fetchAll('SELECT `key`, `value` FROM settings WHERE `key` LIKE "points_%"');
            $map  = [];
            foreach ($rows as $row) $map[$row['key']] = (int)$row['value'];
            return [
                'run'      => $map['points_per_km_run']  ?? DEFAULT_POINTS_RUN,
                'walk'     => $map['points_per_km_walk'] ?? DEFAULT_POINTS_WALK,
                'ride'     => $map['points_per_km_ride'] ?? DEFAULT_POINTS_RIDE,
                'max_day'  => $map['max_points_per_day'] ?? DEFAULT_MAX_DAILY,
            ];
        });
        return self::$rates;
    }

    /**
     * Calculate points for an activity.
     * @param string $activityType Strava activity type (Run, Walk, Ride, etc.)
     * @param float  $distanceMeters
     * @return int
     */
    public static function calculate(string $activityType, float $distanceMeters): int {
        $rates    = self::getRates();
        $km       = $distanceMeters / 1000;
        $type     = strtolower($activityType);

        switch ($type) {
            case 'run':         $pointsPerKm = $rates['run'];  break;
            case 'walk':        $pointsPerKm = $rates['walk']; break;
            case 'ride':
            case 'virtualride': $pointsPerKm = $rates['ride']; break;
            case 'hike':        $pointsPerKm = $rates['walk']; break;
            default:            $pointsPerKm = 0;
        }

        return (int)round($pointsPerKm * $km);
    }

    /**
     * Calculate points considering daily maximum for a user.
     * @param int    $userId
     * @param string $date     Y-m-d
     * @param int    $rawPoints Points to potentially award
     * @return int   Actual points awarded (capped by daily max)
     */
    public static function applyDailyMax(int $userId, string $date, int $rawPoints): int {
        $rates    = self::getRates();
        $maxDay   = $rates['max_day'];
        if ($maxDay <= 0) return $rawPoints;

        $usedToday = (int)Database::fetchScalar(
            'SELECT COALESCE(SUM(points_awarded), 0) FROM (
                SELECT points_awarded FROM strava_activities   WHERE user_id = ? AND DATE(start_date) = ? AND is_flagged = 0
                UNION ALL
                SELECT points_awarded FROM platform_activities WHERE user_id = ? AND DATE(start_date) = ? AND is_flagged = 0
             ) combined',
            [$userId, $date, $userId, $date]
        );

        $remaining = max(0, $maxDay - $usedToday);
        return min($rawPoints, $remaining);
    }

    /**
     * Calculate and return final points for an activity, applying daily max.
     */
    public static function calculateFinal(int $userId, string $activityType, float $distanceMeters, string $startDate): int {
        $raw  = self::calculate($activityType, $distanceMeters);
        $date = date('Y-m-d', strtotime($startDate));
        return self::applyDailyMax($userId, $date, $raw);
    }

    /**
     * Invalidate cached rates (call after settings update).
     */
    public static function invalidateCache(): void {
        self::$rates = null;
        Cache::forget('points_rates');
    }
}

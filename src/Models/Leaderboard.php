<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Cache;

class Leaderboard {
    /**
     * Get top individual users from the points_cache table.
     */
    public static function getIndividual(int $limit = 100, int $offset = 0): array {
        return Database::fetchAll(
            'SELECT
                u.id, u.first_name, u.last_name, u.email,
                c.name AS church_name, c.slug AS church_slug,
                pc.total_points, pc.run_points, pc.walk_points, pc.ride_points,
                pc.activity_count, pc.rank_individual AS user_rank
             FROM points_cache pc
             JOIN users u ON u.id = pc.user_id
             LEFT JOIN churches c ON c.id = u.church_id
             WHERE u.deleted_at IS NULL AND u.is_active = 1 AND u.role = \'user\' AND u.is_paid = 1 AND pc.total_points > 0
             ORDER BY pc.total_points DESC, u.first_name ASC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /**
     * Get all users ranked (for full leaderboard).
     */
    public static function getIndividualAll(): array {
        return self::getIndividual(10000, 0);
    }

    /**
     * Get top users ranked by a specific activity type (run/walk/ride).
     */
    public static function getByActivity(string $type, int $limit = 100): array {
        $col = 'pc.run_points';
        if ($type === 'walk') $col = 'pc.walk_points';
        if ($type === 'ride') $col = 'pc.ride_points';

        return Database::fetchAll(
            "SELECT
                u.id, u.first_name, u.last_name,
                c.name AS church_name,
                {$col} AS activity_points,
                pc.activity_count
             FROM points_cache pc
             JOIN users u ON u.id = pc.user_id
             LEFT JOIN churches c ON c.id = u.church_id
             WHERE u.deleted_at IS NULL AND u.is_active = 1 AND u.role = 'user' AND u.is_paid = 1
               AND {$col} > 0
             ORDER BY {$col} DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get top churches from church_points_cache.
     */
    public static function getChurch(int $limit = 50): array {
        return Database::fetchAll(
            'SELECT
                ch.id, ch.name, ch.slug, ch.logo_url, ch.city,
                cpc.total_points, cpc.member_count, cpc.avg_points, cpc.church_rank AS church_position
             FROM church_points_cache cpc
             JOIN churches ch ON ch.id = cpc.church_id
             WHERE ch.is_active = 1 AND cpc.total_points > 0
             ORDER BY cpc.total_points DESC
             LIMIT ?',
            [$limit]
        );
    }

    /**
     * Get rank and points for a specific user.
     */
    public static function getUserRank(int $userId): array {
        $row = Database::fetchOne(
            'SELECT pc.total_points, pc.run_points, pc.walk_points, pc.ride_points,
                    pc.activity_count, pc.rank_individual AS user_rank
             FROM points_cache pc WHERE pc.user_id = ? LIMIT 1',
            [$userId]
        );
        return $row ?: [
            'total_points'   => 0,
            'run_points'     => 0,
            'walk_points'    => 0,
            'ride_points'    => 0,
            'activity_count' => 0,
            'user_rank'      => null,
        ];
    }

    /**
     * Get the leading church.
     */
    public static function getLeadingChurch(): ?array {
        return Database::fetchOne(
            'SELECT ch.id, ch.name, ch.slug, ch.logo_url, cpc.total_points, cpc.member_count
             FROM church_points_cache cpc
             JOIN churches ch ON ch.id = cpc.church_id
             WHERE ch.is_active = 1
             ORDER BY cpc.total_points DESC
             LIMIT 1'
        );
    }

    /**
     * Quick stats for the homepage.
     */
    public static function getStats(): array {
        return Cache::remember('homepage_stats', CACHE_STATS, function () {
            $users    = (int)Database::fetchScalar("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND is_active = 1 AND role = 'user'");
            $paid     = (int)Database::fetchScalar("SELECT COUNT(*) FROM users WHERE is_paid = 1 AND deleted_at IS NULL AND role = 'user'");
            $points   = (int)Database::fetchScalar('SELECT COALESCE(SUM(total_points), 0) FROM points_cache');
            $churches = (int)Database::fetchScalar('SELECT COUNT(*) FROM churches WHERE is_active = 1');
            $activities = (int)Database::fetchScalar('SELECT COUNT(*) FROM strava_activities WHERE is_flagged = 0');
            return compact('users', 'paid', 'points', 'churches', 'activities');
        });
    }
}

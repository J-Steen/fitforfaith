<?php
namespace App\Models;

use App\Core\Database;

class Activity {
    public static function findByStravaId(int $stravaId): ?array {
        return Database::fetchOne(
            'SELECT * FROM strava_activities WHERE strava_id = ? LIMIT 1',
            [$stravaId]
        );
    }

    /**
     * Insert or update an activity. Returns the row id.
     */
    public static function upsert(array $data): int {
        $existing = self::findByStravaId((int)$data['strava_id']);

        if ($existing) {
            Database::execute(
                'UPDATE strava_activities SET
                    activity_type   = ?,
                    name            = ?,
                    distance_meters = ?,
                    moving_time_sec = ?,
                    start_date      = ?,
                    points_awarded  = ?,
                    raw_payload     = ?
                 WHERE strava_id = ?',
                [
                    $data['activity_type'],
                    $data['name'] ?? null,
                    (float)($data['distance_meters'] ?? 0),
                    (int)($data['moving_time_sec'] ?? 0),
                    $data['start_date'],
                    (int)($data['points_awarded'] ?? 0),
                    $data['raw_payload'] ?? null,
                    $data['strava_id'],
                ]
            );
            return (int)$existing['id'];
        }

        Database::execute(
            'INSERT INTO strava_activities
                (user_id, church_id, strava_id, activity_type, name, distance_meters, moving_time_sec, start_date, points_awarded, raw_payload)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                (int)$data['user_id'],
                $data['church_id'] ?? null,
                (int)$data['strava_id'],
                $data['activity_type'],
                $data['name'] ?? null,
                (float)($data['distance_meters'] ?? 0),
                (int)($data['moving_time_sec'] ?? 0),
                $data['start_date'],
                (int)($data['points_awarded'] ?? 0),
                $data['raw_payload'] ?? null,
            ]
        );
        return Database::lastInsertId();
    }

    public static function deleteByStravaId(int $stravaId): void {
        Database::execute('DELETE FROM strava_activities WHERE strava_id = ?', [$stravaId]);
    }

    public static function getForUser(int $userId, int $limit = 20, int $offset = 0): array {
        return Database::fetchAll(
            'SELECT activity_type, name, distance_meters, moving_time_sec, start_date, points_awarded, "strava" AS source
             FROM strava_activities WHERE user_id = ? AND is_flagged = 0
             UNION ALL
             SELECT activity_type, name, distance_meters, moving_time_sec, start_date, points_awarded, platform AS source
             FROM platform_activities WHERE user_id = ? AND is_flagged = 0
             ORDER BY start_date DESC LIMIT ? OFFSET ?',
            [$userId, $userId, $limit, $offset]
        );
    }

    public static function countForUser(int $userId): int {
        return (int)Database::fetchScalar(
            'SELECT COUNT(*) FROM strava_activities WHERE user_id = ? AND is_flagged = 0',
            [$userId]
        );
    }

    public static function getTotalsForUser(int $userId): array {
        $row = Database::fetchOne(
            'SELECT
                COALESCE(SUM(points_awarded), 0) AS total_points,
                COALESCE(SUM(CASE WHEN activity_type="Run"  THEN points_awarded END), 0) AS run_points,
                COALESCE(SUM(CASE WHEN activity_type="Walk" THEN points_awarded END), 0) AS walk_points,
                COALESCE(SUM(CASE WHEN activity_type="Ride" THEN points_awarded END), 0) AS ride_points,
                COUNT(*) AS activity_count
             FROM (
                SELECT activity_type, points_awarded FROM strava_activities    WHERE user_id = ? AND is_flagged = 0
                UNION ALL
                SELECT activity_type, points_awarded FROM platform_activities  WHERE user_id = ? AND is_flagged = 0
             ) combined',
            [$userId, $userId]
        );
        return $row ?: ['total_points' => 0, 'run_points' => 0, 'walk_points' => 0, 'ride_points' => 0, 'activity_count' => 0];
    }

    public static function flag(int $id): void {
        Database::execute('UPDATE strava_activities SET is_flagged = 1 WHERE id = ?', [$id]);
    }

    public static function unflag(int $id): void {
        Database::execute('UPDATE strava_activities SET is_flagged = 0 WHERE id = ?', [$id]);
    }

    public static function getAllPaginated(int $page = 1, int $perPage = 50, array $filters = []): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['user_id'])) {
            $where[]  = 'sa.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['flagged'])) {
            $where[] = 'sa.is_flagged = 1';
        }
        if (!empty($filters['type'])) {
            $where[]  = 'sa.activity_type = ?';
            $params[] = $filters['type'];
        }
        $whereStr = implode(' AND ', $where);
        $sql = "SELECT sa.*, u.first_name, u.last_name, u.email, u.fitness_platform, c.name AS church_name
                FROM strava_activities sa
                JOIN users u ON u.id = sa.user_id
                LEFT JOIN churches c ON c.id = sa.church_id
                WHERE $whereStr
                ORDER BY sa.start_date DESC";
        return Database::paginate($sql, $params, $page, $perPage);
    }

    public static function getTotalPointsAllUsers(): array {
        return Database::fetchAll(
            'SELECT user_id,
                    SUM(CASE WHEN activity_type="Run"  THEN points_awarded ELSE 0 END) AS run_points,
                    SUM(CASE WHEN activity_type="Walk" THEN points_awarded ELSE 0 END) AS walk_points,
                    SUM(CASE WHEN activity_type="Ride" THEN points_awarded ELSE 0 END) AS ride_points,
                    SUM(points_awarded) AS total_points,
                    COUNT(*) AS activity_count
             FROM (
                SELECT user_id, activity_type, points_awarded FROM strava_activities   WHERE is_flagged = 0
                UNION ALL
                SELECT user_id, activity_type, points_awarded FROM platform_activities WHERE is_flagged = 0
             ) combined
             GROUP BY user_id'
        );
    }
}

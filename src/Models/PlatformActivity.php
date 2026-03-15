<?php
namespace App\Models;

use App\Core\Database;

class PlatformActivity {
    public static function findByExternalId(string $platform, string $externalId): ?array {
        return Database::fetchOne(
            'SELECT * FROM platform_activities WHERE platform = ? AND external_id = ? LIMIT 1',
            [$platform, $externalId]
        );
    }

    public static function upsert(array $data): int {
        $existing = self::findByExternalId($data['platform'], (string)$data['external_id']);
        if ($existing) {
            Database::execute(
                'UPDATE platform_activities SET
                    activity_type   = ?,
                    name            = ?,
                    distance_meters = ?,
                    moving_time_sec = ?,
                    start_date      = ?,
                    points_awarded  = ?,
                    raw_payload     = ?
                 WHERE platform = ? AND external_id = ?',
                [
                    $data['activity_type'],
                    $data['name'] ?? null,
                    (float)($data['distance_meters'] ?? 0),
                    (int)($data['moving_time_sec'] ?? 0),
                    $data['start_date'],
                    (int)($data['points_awarded'] ?? 0),
                    $data['raw_payload'] ?? null,
                    $data['platform'],
                    (string)$data['external_id'],
                ]
            );
            return (int)$existing['id'];
        }

        Database::execute(
            'INSERT INTO platform_activities
                (user_id, church_id, platform, external_id, activity_type, name, distance_meters, moving_time_sec, start_date, points_awarded, raw_payload)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [
                (int)$data['user_id'],
                $data['church_id'] ?? null,
                $data['platform'],
                (string)$data['external_id'],
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

    public static function deleteByExternalId(string $platform, string $externalId): void {
        Database::execute(
            'DELETE FROM platform_activities WHERE platform = ? AND external_id = ?',
            [$platform, $externalId]
        );
    }

    public static function flag(int $id): void {
        Database::execute('UPDATE platform_activities SET is_flagged = 1 WHERE id = ?', [$id]);
    }

    public static function unflag(int $id): void {
        Database::execute('UPDATE platform_activities SET is_flagged = 0 WHERE id = ?', [$id]);
    }

    public static function getTotalsForUser(int $userId): array {
        $row = Database::fetchOne(
            'SELECT
                COALESCE(SUM(points_awarded), 0) AS total_points,
                COALESCE(SUM(CASE WHEN activity_type="Run"  THEN points_awarded END), 0) AS run_points,
                COALESCE(SUM(CASE WHEN activity_type="Walk" THEN points_awarded END), 0) AS walk_points,
                COALESCE(SUM(CASE WHEN activity_type="Ride" THEN points_awarded END), 0) AS ride_points,
                COUNT(*) AS activity_count
             FROM platform_activities
             WHERE user_id = ? AND is_flagged = 0',
            [$userId]
        );
        return $row ?: ['total_points' => 0, 'run_points' => 0, 'walk_points' => 0, 'ride_points' => 0, 'activity_count' => 0];
    }

    public static function getTotalPointsAllUsers(): array {
        return Database::fetchAll(
            'SELECT user_id,
                    SUM(CASE WHEN activity_type="Run"  THEN points_awarded ELSE 0 END) AS run_points,
                    SUM(CASE WHEN activity_type="Walk" THEN points_awarded ELSE 0 END) AS walk_points,
                    SUM(CASE WHEN activity_type="Ride" THEN points_awarded ELSE 0 END) AS ride_points,
                    SUM(points_awarded) AS total_points,
                    COUNT(*) AS activity_count
             FROM platform_activities
             WHERE is_flagged = 0
             GROUP BY user_id'
        );
    }
}

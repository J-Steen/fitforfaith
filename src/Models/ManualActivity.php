<?php
namespace App\Models;

use App\Core\Database;

class ManualActivity {
    const PLATFORMS = [
        'garmin'       => 'Garmin Connect',
        'fitbit'       => 'Fitbit',
        'apple_health' => 'Apple Health / Watch',
        'polar'        => 'Polar Flow',
        'suunto'       => 'Suunto',
        'samsung'      => 'Samsung Health',
        'wahoo'        => 'Wahoo',
        'other'        => 'Other / Manual',
    ];

    public static function create(array $data): int {
        Database::execute(
            'INSERT INTO manual_activities
                (user_id, church_id, activity_type, platform, name, distance_meters, moving_time_sec, start_date, notes)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [
                (int)$data['user_id'],
                $data['church_id'] ?? null,
                $data['activity_type'],
                $data['platform'] ?? 'other',
                $data['name'] ?? null,
                (float)($data['distance_meters'] ?? 0),
                (int)($data['moving_time_sec'] ?? 0),
                $data['start_date'],
                $data['notes'] ?? null,
            ]
        );
        return Database::lastInsertId();
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne('SELECT * FROM manual_activities WHERE id = ?', [$id]);
    }

    public static function getForUser(int $userId, int $limit = 20, int $offset = 0): array {
        return Database::fetchAll(
            'SELECT * FROM manual_activities WHERE user_id = ? ORDER BY start_date DESC, created_at DESC LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }

    public static function approve(int $id, int $adminId, int $points): void {
        Database::execute(
            'UPDATE manual_activities SET status = "approved", reviewed_by = ?, reviewed_at = NOW(), points_awarded = ? WHERE id = ?',
            [$adminId, $points, $id]
        );
    }

    public static function reject(int $id, int $adminId, string $reason = ''): void {
        Database::execute(
            'UPDATE manual_activities SET status = "rejected", reviewed_by = ?, reviewed_at = NOW(), reject_reason = ?, points_awarded = 0 WHERE id = ?',
            [$adminId, $reason, $id]
        );
    }

    public static function getPendingCount(): int {
        return (int)Database::fetchScalar('SELECT COUNT(*) FROM manual_activities WHERE status = "pending"');
    }

    public static function getAllPaginated(int $page = 1, int $perPage = 50, array $filters = []): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[]  = 'ma.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $where[]  = 'ma.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['type'])) {
            $where[]  = 'ma.activity_type = ?';
            $params[] = $filters['type'];
        }
        $whereStr = implode(' AND ', $where);
        $sql = "SELECT ma.*, u.first_name, u.last_name, u.email, c.name AS church_name
                FROM manual_activities ma
                JOIN users u ON u.id = ma.user_id
                LEFT JOIN churches c ON c.id = ma.church_id
                WHERE $whereStr
                ORDER BY ma.created_at DESC";
        return Database::paginate($sql, $params, $page, $perPage);
    }
}

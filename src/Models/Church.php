<?php
namespace App\Models;

use App\Core\Database;

class Church {
    public static function all(bool $onlyActive = true): array {
        $where = $onlyActive ? 'WHERE is_active = 1' : '';
        return Database::fetchAll("SELECT * FROM churches $where ORDER BY name ASC");
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne('SELECT * FROM churches WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findBySlug(string $slug): ?array {
        return Database::fetchOne('SELECT * FROM churches WHERE slug = ? LIMIT 1', [$slug]);
    }

    public static function create(array $data): int {
        Database::execute(
            'INSERT INTO churches (name, slug, city, description, logo_url, is_active) VALUES (?,?,?,?,?,?)',
            [
                trim($data['name']),
                slugify($data['slug'] ?? $data['name']),
                $data['city'] ?? null,
                $data['description'] ?? null,
                $data['logo_url'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ]
        );
        $id = Database::lastInsertId();
        // Initialize cache row
        Database::execute('INSERT IGNORE INTO church_points_cache (church_id) VALUES (?)', [$id]);
        return $id;
    }

    public static function update(int $id, array $data): void {
        $allowed = ['name', 'slug', 'city', 'description', 'logo_url', 'is_active'];
        $sets = [];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]   = "`$field` = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($sets)) return;
        $params[] = $id;
        Database::execute('UPDATE churches SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function delete(int $id): void {
        Database::execute('DELETE FROM churches WHERE id = ?', [$id]);
    }

    public static function getMemberCount(int $id): int {
        return (int)Database::fetchScalar(
            'SELECT COUNT(*) FROM users WHERE church_id = ? AND deleted_at IS NULL',
            [$id]
        );
    }

    public static function count(): int {
        return (int)Database::fetchScalar('SELECT COUNT(*) FROM churches WHERE is_active = 1');
    }

    /**
     * Get church list with member counts and points (for admin).
     */
    public static function allWithStats(): array {
        return Database::fetchAll(
            'SELECT c.*,
                    COALESCE(pc.total_points, 0) AS total_points,
                    COALESCE(pc.member_count, 0) AS member_count,
                    COALESCE(pc.church_rank, 0)  AS church_rank
             FROM churches c
             LEFT JOIN church_points_cache pc ON pc.church_id = c.id
             ORDER BY total_points DESC'
        );
    }
}

<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Auth;

class User {
    /** All supported fitness platforms. Strava = auto-sync; others = manual activity logging. */
    const PLATFORMS = [
        'strava' => ['label' => 'Strava', 'icon' => '⚡', 'auto' => true],
    ];

    public static function platformLabel(string $platform): string {
        return self::PLATFORMS[$platform]['label'] ?? ucfirst($platform);
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne(
            'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$id]
        );
    }

    public static function findByEmail(string $email): ?array {
        return Database::fetchOne(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public static function findByStravaId(int $athleteId): ?array {
        return Database::fetchOne(
            'SELECT * FROM users WHERE strava_athlete_id = ? AND deleted_at IS NULL LIMIT 1',
            [$athleteId]
        );
    }

    public static function findByResetToken(string $token): ?array {
        return Database::fetchOne(
            'SELECT * FROM users WHERE pw_reset_token = ? AND pw_reset_expires > NOW() AND deleted_at IS NULL LIMIT 1',
            [$token]
        );
    }

    /**
     * Create a new user. Returns the new user ID.
     */
    public static function create(array $data): int {
        Database::execute(
            'INSERT INTO users (first_name, last_name, email, password_hash, church_id, phone, fitness_platform, registration_ref, role, language)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['first_name']),
                trim($data['last_name']),
                strtolower(trim($data['email'])),
                Auth::hashPassword($data['password']),
                $data['church_id'] ?: null,
                $data['phone'] ?? null,
                $data['fitness_platform'] ?? null,
                $data['registration_ref'] ?? null,
                $data['role'] ?? 'user',
                $data['language'] ?? 'en',
            ]
        );
        $id = Database::lastInsertId();
        // Initialize points cache row
        Database::execute(
            'INSERT IGNORE INTO points_cache (user_id) VALUES (?)',
            [$id]
        );
        return $id;
    }

    /**
     * Update user profile fields.
     */
    public static function update(int $id, array $data): void {
        $allowed = ['first_name', 'last_name', 'phone', 'church_id', 'fitness_platform', 'language'];
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
        Database::execute(
            'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $params
        );
    }

    /**
     * Update password.
     */
    public static function updatePassword(int $id, string $newPassword): void {
        Database::execute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [Auth::hashPassword($newPassword), $id]
        );
    }

    /**
     * Soft delete a user.
     */
    public static function delete(int $id): void {
        Database::execute(
            'UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = ?',
            [$id]
        );
    }

    /**
     * Mark user as paid.
     */
    public static function markPaid(int $id): void {
        Database::execute('UPDATE users SET is_paid = 1 WHERE id = ?', [$id]);
    }

    /**
     * Link Strava account.
     */
    public static function linkStrava(int $userId, int $athleteId, string $accessToken, string $refreshToken, int $expiresAt): void {
        Database::execute(
            'UPDATE users SET strava_athlete_id = ?, strava_access_token = ?, strava_refresh_token = ?,
             strava_token_expires = ?, strava_connected_at = NOW() WHERE id = ?',
            [$athleteId, $accessToken, $refreshToken, $expiresAt, $userId]
        );
    }

    /**
     * Update Strava tokens (after refresh).
     */
    public static function updateStravaTokens(int $userId, string $accessToken, string $refreshToken, int $expiresAt): void {
        Database::execute(
            'UPDATE users SET strava_access_token = ?, strava_refresh_token = ?, strava_token_expires = ? WHERE id = ?',
            [$accessToken, $refreshToken, $expiresAt, $userId]
        );
    }

    /**
     * Disconnect Strava.
     */
    public static function unlinkStrava(int $userId): void {
        Database::execute(
            'UPDATE users SET strava_athlete_id = NULL, strava_access_token = NULL,
             strava_refresh_token = NULL, strava_token_expires = NULL, strava_connected_at = NULL WHERE id = ?',
            [$userId]
        );
    }

    /**
     * Set password reset token.
     */
    public static function setResetToken(int $id): string {
        $token = bin2hex(random_bytes(32));
        Database::execute(
            'UPDATE users SET pw_reset_token = ?, pw_reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?',
            [$token, $id]
        );
        return $token;
    }

    /**
     * Generate and store an email verification token. Returns the token.
     */
    public static function generateEmailToken(int $id): string {
        $token = bin2hex(random_bytes(32));
        Database::execute('UPDATE users SET email_token = ? WHERE id = ?', [$token, $id]);
        return $token;
    }

    /**
     * Find a user by email verification token.
     */
    public static function findByEmailToken(string $token): ?array {
        return Database::fetchOne(
            'SELECT * FROM users WHERE email_token = ? AND deleted_at IS NULL LIMIT 1',
            [$token]
        );
    }

    /**
     * Mark email as verified, clear the token.
     */
    public static function verifyEmail(int $id): void {
        Database::execute(
            'UPDATE users SET email_verified_at = NOW(), email_token = NULL WHERE id = ?',
            [$id]
        );
    }

    /**
     * Consume reset token and update password.
     */
    public static function consumeResetToken(string $token, string $newPassword): bool {
        $user = self::findByResetToken($token);
        if (!$user) return false;
        Database::execute(
            'UPDATE users SET password_hash = ?, pw_reset_token = NULL, pw_reset_expires = NULL WHERE id = ?',
            [Auth::hashPassword($newPassword), $user['id']]
        );
        return true;
    }

    /**
     * Get paginated user list for admin.
     */
    public static function allPaginated(int $page = 1, int $perPage = 50, array $filters = []): array {
        $where  = ['u.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s]);
        }
        if (isset($filters['is_paid'])) {
            $where[]  = 'u.is_paid = ?';
            $params[] = (int)$filters['is_paid'];
        }
        if (!empty($filters['church_id'])) {
            $where[]  = 'u.church_id = ?';
            $params[] = (int)$filters['church_id'];
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT u.*, c.name AS church_name,
                       COALESCE(pc.total_points, 0) AS total_points,
                       COALESCE(pc.rank_individual, 0) AS user_rank
                FROM users u
                LEFT JOIN churches c ON c.id = u.church_id
                LEFT JOIN points_cache pc ON pc.user_id = u.id
                WHERE $whereStr
                ORDER BY u.created_at DESC";

        return Database::paginate($sql, $params, $page, $perPage);
    }

    /**
     * Count total registered (non-deleted) users.
     */
    public static function count(): int {
        return (int)Database::fetchScalar('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL');
    }

    /**
     * Count paid users.
     */
    public static function countPaid(): int {
        return (int)Database::fetchScalar('SELECT COUNT(*) FROM users WHERE is_paid = 1 AND deleted_at IS NULL');
    }

    /**
     * Get users with Strava connected, for token refresh cron.
     */
    public static function withStravaTokens(): array {
        return Database::fetchAll(
            'SELECT id, strava_athlete_id, strava_access_token, strava_refresh_token, strava_token_expires
             FROM users
             WHERE strava_athlete_id IS NOT NULL AND is_active = 1 AND deleted_at IS NULL'
        );
    }

    /**
     * Full name helper.
     */
    public static function fullName(array $user): string {
        return trim($user['first_name'] . ' ' . $user['last_name']);
    }

    /**
     * Check if user has Strava connected.
     */
    public static function hasStrava(array $user): bool {
        return !empty($user['strava_athlete_id']);
    }
}

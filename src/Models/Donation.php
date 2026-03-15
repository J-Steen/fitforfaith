<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Cache;

class Donation {
    public static function findById(int $id): ?array {
        return Database::fetchOne('SELECT * FROM donations WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findByPayFastId(string $pfPaymentId): ?array {
        return Database::fetchOne('SELECT * FROM donations WHERE pf_payment_id = ? LIMIT 1', [$pfPaymentId]);
    }

    /**
     * Create a pending donation. Returns new ID.
     */
    public static function createPending(int $userId, int $churchId, int $amountCents): int {
        Database::execute(
            'INSERT INTO donations (user_id, church_id, amount_cents, status) VALUES (?,?,?,?)',
            [$userId, $churchId, $amountCents, 'pending']
        );
        return Database::lastInsertId();
    }

    /**
     * Mark donation as complete after successful PayFast ITN.
     */
    public static function markComplete(int $id, string $pfPaymentId, string $itnPayload): void {
        Database::execute(
            'UPDATE donations SET
                status = "complete",
                pf_payment_id = ?,
                itn_verified = 1,
                itn_received_at = NOW(),
                itn_payload = ?
             WHERE id = ? AND status != "complete"',
            [$pfPaymentId, $itnPayload, $id]
        );
        Cache::forget('homepage_stats');
        Cache::forget('total_raised');
    }

    public static function markFailed(int $id, string $pfPaymentId = ''): void {
        Database::execute(
            'UPDATE donations SET status = "failed", pf_payment_id = ? WHERE id = ?',
            [$pfPaymentId ?: null, $id]
        );
    }

    public static function getForUser(int $userId): array {
        return Database::fetchAll(
            'SELECT * FROM donations WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    public static function getLatestForUser(int $userId): ?array {
        return Database::fetchOne(
            'SELECT * FROM donations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
            [$userId]
        );
    }

    public static function userHasPaid(int $userId): bool {
        return (bool)Database::fetchScalar(
            'SELECT COUNT(*) FROM donations WHERE user_id = ? AND status = "complete"',
            [$userId]
        );
    }

    public static function totalRaisedCents(): int {
        return (int)Cache::remember('total_raised', CACHE_STATS, fn() =>
            Database::fetchScalar('SELECT COALESCE(SUM(amount_cents), 0) FROM donations WHERE status = "complete"')
        );
    }

    public static function getAllPaginated(int $page = 1, int $perPage = 50): array {
        $sql = 'SELECT d.*, u.first_name, u.last_name, u.email, c.name AS church_name
                FROM donations d
                LEFT JOIN users u ON u.id = d.user_id
                LEFT JOIN churches c ON c.id = d.church_id
                ORDER BY d.created_at DESC';
        return Database::paginate($sql, [], $page, $perPage);
    }
}

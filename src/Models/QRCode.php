<?php
namespace App\Models;

use App\Core\Database;

class QRCode {
    public static function create(string $label, ?int $churchId, ?int $createdBy, ?string $expiresAt = null): string {
        $token = bin2hex(random_bytes(16)); // 32 char hex
        Database::execute(
            'INSERT INTO qr_codes (token, label, church_id, created_by, expires_at) VALUES (?,?,?,?,?)',
            [$token, $label, $churchId, $createdBy, $expiresAt]
        );
        return $token;
    }

    public static function findByToken(string $token): ?array {
        return Database::fetchOne(
            'SELECT qr.*, c.name AS church_name, c.slug AS church_slug
             FROM qr_codes qr
             LEFT JOIN churches c ON c.id = qr.church_id
             WHERE qr.token = ?
               AND qr.is_active = 1
               AND (qr.expires_at IS NULL OR qr.expires_at > NOW())
             LIMIT 1',
            [$token]
        );
    }

    public static function incrementScans(string $token): void {
        Database::execute('UPDATE qr_codes SET scans = scans + 1 WHERE token = ?', [$token]);
    }

    public static function allForAdmin(): array {
        return Database::fetchAll(
            'SELECT qr.*, c.name AS church_name, u.first_name, u.last_name
             FROM qr_codes qr
             LEFT JOIN churches c ON c.id = qr.church_id
             LEFT JOIN users u ON u.id = qr.created_by
             ORDER BY qr.created_at DESC'
        );
    }

    public static function deactivate(int $id): void {
        Database::execute('UPDATE qr_codes SET is_active = 0 WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void {
        Database::execute('DELETE FROM qr_codes WHERE id = ?', [$id]);
    }
}

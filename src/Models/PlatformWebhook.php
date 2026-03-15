<?php
namespace App\Models;

use App\Core\Database;

class PlatformWebhook {
    public static function queue(string $platform, string $eventType, ?string $platformUid, ?string $externalId, ?string $payload = null): void {
        Database::execute(
            'INSERT INTO platform_webhook_queue (platform, event_type, platform_uid, external_id, payload) VALUES (?,?,?,?,?)',
            [$platform, $eventType, $platformUid, $externalId, $payload]
        );
    }

    public static function getPending(int $limit = 50): array {
        return Database::fetchAll(
            'SELECT * FROM platform_webhook_queue
             WHERE status = "pending" AND attempts < 5
             ORDER BY created_at ASC LIMIT ?',
            [$limit]
        );
    }

    public static function markProcessing(int $id): void {
        Database::execute(
            'UPDATE platform_webhook_queue SET status = "processing", attempts = attempts + 1 WHERE id = ?',
            [$id]
        );
    }

    public static function markDone(int $id): void {
        Database::execute(
            'UPDATE platform_webhook_queue SET status = "done", processed_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    public static function markFailed(int $id, string $error = ''): void {
        Database::execute(
            'UPDATE platform_webhook_queue SET status = "failed", error_msg = ? WHERE id = ?',
            [substr($error, 0, 500), $id]
        );
    }

    public static function resetStuck(): void {
        Database::execute(
            'UPDATE platform_webhook_queue SET status = "pending"
             WHERE status = "processing" AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)'
        );
    }
}

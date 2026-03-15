<?php
namespace App\Models;

use App\Core\Database;

class Webhook {
    public static function queue(array $payload): int {
        Database::execute(
            'INSERT INTO strava_webhook_events (event_type, aspect_type, object_id, owner_id, payload)
             VALUES (?,?,?,?,?)',
            [
                $payload['object_type'] ?? 'activity',
                $payload['aspect_type'] ?? 'create',
                (int)($payload['object_id'] ?? 0),
                (int)($payload['owner_id'] ?? 0),
                json_encode($payload),
            ]
        );
        return Database::lastInsertId();
    }

    public static function getPending(int $limit = 50): array {
        return Database::fetchAll(
            'SELECT * FROM strava_webhook_events
             WHERE status = "pending" AND attempts < 5
             ORDER BY received_at ASC
             LIMIT ?',
            [$limit]
        );
    }

    public static function markProcessing(int $id): void {
        Database::execute(
            'UPDATE strava_webhook_events SET status = "processing", attempts = attempts + 1 WHERE id = ?',
            [$id]
        );
    }

    public static function markDone(int $id): void {
        Database::execute(
            'UPDATE strava_webhook_events SET status = "done", processed_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    public static function markFailed(int $id, string $error = ''): void {
        Database::execute(
            'UPDATE strava_webhook_events SET status = "failed", error_message = ? WHERE id = ?',
            [substr($error, 0, 500), $id]
        );
    }

    public static function resetStuck(): void {
        // Reset events stuck in "processing" for more than 5 minutes
        Database::execute(
            'UPDATE strava_webhook_events SET status = "pending"
             WHERE status = "processing" AND received_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)'
        );
    }
}

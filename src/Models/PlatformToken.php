<?php
namespace App\Models;

use App\Core\Database;

class PlatformToken {
    public static function find(int $userId, string $platform): ?array {
        return Database::fetchOne(
            'SELECT * FROM user_platform_tokens WHERE user_id = ? AND platform = ? LIMIT 1',
            [$userId, $platform]
        );
    }

    public static function store(int $userId, string $platform, array $data): void {
        Database::execute(
            'INSERT INTO user_platform_tokens (user_id, platform, access_token, refresh_token, token_expires, platform_user_id, connected_at)
             VALUES (?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE
               access_token     = VALUES(access_token),
               refresh_token    = VALUES(refresh_token),
               token_expires    = VALUES(token_expires),
               platform_user_id = VALUES(platform_user_id),
               updated_at       = NOW()',
            [
                $userId,
                $platform,
                $data['access_token'],
                $data['refresh_token'] ?? null,
                $data['token_expires']  ?? null,
                $data['platform_user_id'] ?? null,
            ]
        );
    }

    public static function updateTokens(int $userId, string $platform, string $accessToken, ?string $refreshToken, ?int $expires): void {
        Database::execute(
            'UPDATE user_platform_tokens SET access_token = ?, refresh_token = COALESCE(?, refresh_token), token_expires = ?, updated_at = NOW()
             WHERE user_id = ? AND platform = ?',
            [$accessToken, $refreshToken, $expires, $userId, $platform]
        );
    }

    public static function delete(int $userId, string $platform): void {
        Database::execute(
            'DELETE FROM user_platform_tokens WHERE user_id = ? AND platform = ?',
            [$userId, $platform]
        );
    }

    /** Returns array keyed by platform name for the given user. */
    public static function getForUser(int $userId): array {
        $rows = Database::fetchAll(
            'SELECT * FROM user_platform_tokens WHERE user_id = ?',
            [$userId]
        );
        $map = [];
        foreach ($rows as $row) $map[$row['platform']] = $row;
        return $map;
    }

    public static function findByPlatformUserId(string $platform, string $platformUserId): ?array {
        return Database::fetchOne(
            'SELECT * FROM user_platform_tokens WHERE platform = ? AND platform_user_id = ? LIMIT 1',
            [$platform, $platformUserId]
        );
    }

    /** Returns user_id or null. */
    public static function resolveUser(string $platform, string $platformUserId): ?int {
        $row = self::findByPlatformUserId($platform, $platformUserId);
        return $row ? (int)$row['user_id'] : null;
    }

    public static function needsRefresh(array $token, int $bufferSec = 300): bool {
        return $token['token_expires'] && $token['token_expires'] < (time() + $bufferSec);
    }
}

<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Cache;
use App\Models\Activity;

class LeaderboardCacheService {
    /**
     * Full leaderboard rebuild. Called by cron every 5 minutes.
     * Updates points_cache and church_points_cache tables.
     */
    public static function rebuild(): void {
        $start = microtime(true);

        Database::beginTransaction();
        try {
            // Step 1: Aggregate all user points from activities
            $userTotals = Activity::getTotalPointsAllUsers();

            // Step 2: Build a map of user points
            $userMap = [];
            foreach ($userTotals as $row) {
                $userMap[(int)$row['user_id']] = $row;
            }

            // Step 3: Get all active users with their church
            $allUsers = Database::fetchAll(
                "SELECT id, church_id FROM users WHERE is_active = 1 AND deleted_at IS NULL AND role = 'user'"
            );

            // Step 4: Upsert points_cache for every user
            foreach ($allUsers as $user) {
                $uid    = (int)$user['id'];
                $totals = $userMap[$uid] ?? [
                    'total_points'   => 0,
                    'run_points'     => 0,
                    'walk_points'    => 0,
                    'ride_points'    => 0,
                    'activity_count' => 0,
                ];
                Database::execute(
                    'INSERT INTO points_cache (user_id, total_points, run_points, walk_points, ride_points, activity_count, last_rebuilt)
                     VALUES (?,?,?,?,?,?,NOW())
                     ON DUPLICATE KEY UPDATE
                       total_points    = VALUES(total_points),
                       run_points      = VALUES(run_points),
                       walk_points     = VALUES(walk_points),
                       ride_points     = VALUES(ride_points),
                       activity_count  = VALUES(activity_count),
                       last_rebuilt    = VALUES(last_rebuilt)',
                    [
                        $uid,
                        (int)$totals['total_points'],
                        (int)$totals['run_points'],
                        (int)$totals['walk_points'],
                        (int)$totals['ride_points'],
                        (int)$totals['activity_count'],
                    ]
                );
            }

            // Step 5: Assign individual ranks (PHP-side, avoids MySQL reserved word issues)
            $ranked = Database::fetchAll(
                'SELECT user_id FROM points_cache WHERE total_points > 0 ORDER BY total_points DESC'
            );
            Database::execute('UPDATE points_cache SET rank_individual = NULL');
            foreach ($ranked as $pos => $row) {
                Database::execute(
                    'UPDATE points_cache SET rank_individual = ? WHERE user_id = ?',
                    [$pos + 1, $row['user_id']]
                );
            }

            // Step 6: Aggregate church totals
            $churchTotals = Database::fetchAll(
                'SELECT u.church_id,
                        SUM(COALESCE(pc.total_points, 0)) AS total_points,
                        COUNT(u.id) AS member_count
                 FROM users u
                 LEFT JOIN points_cache pc ON pc.user_id = u.id
                 WHERE u.church_id IS NOT NULL AND u.is_active = 1 AND u.deleted_at IS NULL AND u.role = \'user\'
                 GROUP BY u.church_id'
            );

            // Step 7: Upsert church_points_cache
            foreach ($churchTotals as $row) {
                $avg = $row['member_count'] > 0
                    ? round($row['total_points'] / $row['member_count'], 2)
                    : 0;
                Database::execute(
                    'INSERT INTO church_points_cache (church_id, total_points, member_count, avg_points, last_rebuilt)
                     VALUES (?,?,?,?,NOW())
                     ON DUPLICATE KEY UPDATE
                       total_points  = VALUES(total_points),
                       member_count  = VALUES(member_count),
                       avg_points    = VALUES(avg_points),
                       last_rebuilt  = VALUES(last_rebuilt)',
                    [
                        (int)$row['church_id'],
                        (int)$row['total_points'],
                        (int)$row['member_count'],
                        $avg,
                    ]
                );
            }

            // Step 8: Assign church ranks (PHP-side)
            $cranked = Database::fetchAll(
                'SELECT church_id FROM church_points_cache WHERE total_points > 0 ORDER BY total_points DESC'
            );
            Database::execute('UPDATE church_points_cache SET church_rank = NULL');
            foreach ($cranked as $pos => $row) {
                Database::execute(
                    'UPDATE church_points_cache SET church_rank = ? WHERE church_id = ?',
                    [$pos + 1, $row['church_id']]
                );
            }

            Database::commit();

            // Bust public cache
            Cache::forget('homepage_stats');
            Cache::forget('homepage_stats');
            Cache::forget('leaderboard_individual');
            Cache::forget('leaderboard_church');

            $elapsed = round(microtime(true) - $start, 3);
            app_log("Leaderboard rebuilt in {$elapsed}s — " . count($allUsers) . " users, " . count($churchTotals) . " churches");

        } catch (\Throwable $e) {
            Database::rollback();
            app_log('Leaderboard rebuild failed: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Quick targeted rebuild for a single user (called immediately after webhook processing).
     */
    public static function rebuildUser(int $userId): void {
        $totals = \App\Models\Activity::getTotalsForUser($userId);
        Database::execute(
            'INSERT INTO points_cache (user_id, total_points, run_points, walk_points, ride_points, activity_count, last_rebuilt)
             VALUES (?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE
               total_points    = VALUES(total_points),
               run_points      = VALUES(run_points),
               walk_points     = VALUES(walk_points),
               ride_points     = VALUES(ride_points),
               activity_count  = VALUES(activity_count),
               last_rebuilt    = VALUES(last_rebuilt)',
            [
                $userId,
                (int)$totals['total_points'],
                (int)$totals['run_points'],
                (int)$totals['walk_points'],
                (int)$totals['ride_points'],
                (int)$totals['activity_count'],
            ]
        );
        // Also update church cache for this user's church
        $user = \App\Models\User::findById($userId);
        if ($user && $user['church_id']) {
            self::rebuildChurch((int)$user['church_id']);
        }
        Cache::forget('homepage_stats');
    }

    private static function rebuildChurch(int $churchId): void {
        $row = Database::fetchOne(
            'SELECT SUM(COALESCE(pc.total_points,0)) AS total_points, COUNT(u.id) AS member_count
             FROM users u
             LEFT JOIN points_cache pc ON pc.user_id = u.id
             WHERE u.church_id = ? AND u.is_active = 1 AND u.deleted_at IS NULL AND u.role = \'user\'',
            [$churchId]
        );
        if (!$row) return;
        $avg = $row['member_count'] > 0 ? round($row['total_points'] / $row['member_count'], 2) : 0;
        Database::execute(
            'INSERT INTO church_points_cache (church_id, total_points, member_count, avg_points, last_rebuilt)
             VALUES (?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE
               total_points  = VALUES(total_points),
               member_count  = VALUES(member_count),
               avg_points    = VALUES(avg_points),
               last_rebuilt  = VALUES(last_rebuilt)',
            [$churchId, (int)$row['total_points'], (int)$row['member_count'], $avg]
        );
    }
}

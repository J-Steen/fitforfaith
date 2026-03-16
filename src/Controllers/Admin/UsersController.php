<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Models\Church;
use App\Models\Activity;
use App\Services\StravaService;
use App\Services\PointsService;
use App\Services\LeaderboardCacheService;

class UsersController {
    public function index(array $params): void {
        Auth::requireAdmin();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'search'   => $_GET['search'] ?? '',
            'is_paid'  => isset($_GET['paid']) ? (int)$_GET['paid'] : null,
            'church_id'=> $_GET['church_id'] ?? '',
        ];
        $result   = User::allPaginated($page, 50, $filters);
        $churches = Church::all();
        $pageTitle = 'Manage Users — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/users/index.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function show(array $params): void {
        Auth::requireAdmin();
        $user = User::findById((int)($params['id'] ?? 0));
        if (!$user) { http_response_code(404); echo '404'; exit; }

        $page       = max(1, (int)($_GET['page'] ?? 1));
        $activities = Activity::getAllPaginated($page, 30, ['user_id' => (int)$user['id']]);
        $totals     = Activity::getTotalsForUser((int)$user['id']);

        $pageTitle = h($user['first_name'] . ' ' . $user['last_name']) . ' — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/users/show.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function edit(array $params): void {
        Auth::requireAdmin();
        $user = User::findById((int)($params['id'] ?? 0));
        if (!$user) { http_response_code(404); echo '404'; exit; }
        $churches  = Church::all();
        $errors    = Session::getFlash('errors') ?? [];

        $stravaStats = Database::fetchOne(
            'SELECT COUNT(*) AS count,
                    MAX(start_date) AS last_date,
                    COALESCE(SUM(points_awarded), 0) AS total_points
             FROM strava_activities
             WHERE user_id = ? AND is_flagged = 0',
            [(int)$user['id']]
        ) ?: ['count' => 0, 'last_date' => null, 'total_points' => 0];

        $pageTitle = 'Edit User — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/users/edit.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function stravaSync(array $params): void {
        Auth::requireAdmin();
        $id   = (int)($params['id'] ?? 0);
        $user = User::findById($id);
        if (!$user) redirect('admin/users');

        if (!$user['strava_athlete_id']) {
            Session::flash('error', 'This user has no Strava connection.');
            redirect('admin/users/' . $id . '/edit');
        }

        try {
            $accessToken = StravaService::ensureFreshToken($id);

            // Fetch all activities (from beginning of time)
            $activities = StravaService::fetchAllActivitiesSince($accessToken, 0);

            $scoredTypes = ['Run', 'Walk', 'Ride', 'VirtualRide', 'Hike'];
            $imported    = 0;
            $churchId    = $user['church_id'] ? (int)$user['church_id'] : null;

            foreach ($activities as $act) {
                if (!in_array($act['type'] ?? '', $scoredTypes, true)) continue;

                $points = PointsService::calculateFinal(
                    $id,
                    $act['type'],
                    (float)($act['distance'] ?? 0),
                    $act['start_date_local'] ?? $act['start_date'] ?? date('Y-m-d H:i:s')
                );

                Activity::upsert([
                    'user_id'         => $id,
                    'church_id'       => $churchId,
                    'strava_id'       => (int)$act['id'],
                    'activity_type'   => $act['type'],
                    'name'            => $act['name'] ?? null,
                    'distance_meters' => (float)($act['distance'] ?? 0),
                    'moving_time_sec' => (int)($act['moving_time'] ?? 0),
                    'start_date'      => date('Y-m-d H:i:s', strtotime($act['start_date_local'] ?? $act['start_date'] ?? 'now')),
                    'points_awarded'  => $points,
                    'raw_payload'     => json_encode($act),
                ]);
                $imported++;
            }

            LeaderboardCacheService::rebuildUser($id);

            Session::flash('success', 'Synced ' . $imported . ' activities for ' . $user['first_name'] . ' ' . $user['last_name'] . '.');

        } catch (\Throwable $e) {
            app_log('Admin Strava sync error for user ' . $id . ': ' . $e->getMessage(), 'ERROR');
            Session::flash('error', 'Sync failed: ' . $e->getMessage());
        }

        redirect('admin/users/' . $id . '/edit');
    }

    public function stravaDisconnect(array $params): void {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $user = User::findById($id);
        if (!$user) redirect('admin/users');

        Database::execute(
            'UPDATE users SET
                strava_athlete_id    = NULL,
                strava_access_token  = NULL,
                strava_refresh_token = NULL,
                strava_token_expires = NULL,
                strava_connected_at  = NULL,
                fitness_platform     = NULL
             WHERE id = ?',
            [$id]
        );

        Session::flash('success', 'Strava disconnected for ' . $user['first_name'] . ' ' . $user['last_name'] . '.');
        redirect('admin/users/' . $id . '/edit');
    }

    public function update(array $params): void {
        Auth::requireAdmin();
        $id   = (int)($params['id'] ?? 0);
        $user = User::findById($id);
        if (!$user) redirect('admin/users');

        $platform = $_POST['fitness_platform'] ?? null;
        if ($platform && !array_key_exists($platform, User::PLATFORMS)) $platform = null;

        User::update($id, [
            'first_name'       => $_POST['first_name'] ?? $user['first_name'],
            'last_name'        => $_POST['last_name']  ?? $user['last_name'],
            'church_id'        => (int)($_POST['church_id'] ?? 0) ?: null,
            'phone'            => $_POST['phone'] ?? null,
            'fitness_platform' => $platform,
        ]);

        // Toggle paid status
        if (isset($_POST['is_paid'])) {
            \App\Core\Database::execute('UPDATE users SET is_paid = ? WHERE id = ?', [(int)$_POST['is_paid'], $id]);
        }
        // Toggle active status
        if (isset($_POST['is_active'])) {
            \App\Core\Database::execute('UPDATE users SET is_active = ? WHERE id = ?', [(int)$_POST['is_active'], $id]);
        }
        // Toggle role
        if (isset($_POST['role']) && in_array($_POST['role'], ['user', 'admin'])) {
            \App\Core\Database::execute('UPDATE users SET role = ? WHERE id = ?', [$_POST['role'], $id]);
        }

        Session::flash('success', 'User updated.');
        redirect('admin/users/' . $id . '/edit');
    }

    public function toggleActive(array $params): void {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);
        if ($id === Auth::id()) {
            Session::flash('error', 'You cannot disable your own account.');
            redirect('admin/users');
        }
        $user = User::findById($id);
        if (!$user) redirect('admin/users');
        $newState = $user['is_active'] ? 0 : 1;
        \App\Core\Database::execute('UPDATE users SET is_active = ? WHERE id = ?', [$newState, $id]);
        Session::flash('success', $newState ? 'User enabled.' : 'User disabled.');
        redirect('admin/users');
    }

    public function delete(array $params): void {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);
        // Don't allow deleting yourself
        if ($id === Auth::id()) {
            Session::flash('error', 'You cannot delete your own account.');
            redirect('admin/users');
        }
        User::delete($id);
        Session::flash('success', 'User deleted.');
        redirect('admin/users');
    }
}

<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Activity;
use App\Models\Leaderboard;
use App\Models\Donation;
use App\Models\User;

class DashboardController {
    public function index(array $params): void {
        Auth::require();
        $user = Auth::user();
        if (($user['role'] ?? '') === 'admin') {
            header('Location: ' . url('admin'), true, 302);
            exit;
        }
        $rank       = Leaderboard::getUserRank((int)$user['id']);
        $activities = Activity::getForUser((int)$user['id'], 10);
        $donation   = Donation::getLatestForUser((int)$user['id']);

        // Church rank
        $churchRank = null;
        if ($user['church_id']) {
            $churchRank = \App\Core\Database::fetchOne(
                'SELECT ch.name, cpc.church_rank, cpc.total_points
                 FROM church_points_cache cpc
                 JOIN churches ch ON ch.id = cpc.church_id
                 WHERE cpc.church_id = ?',
                [$user['church_id']]
            );
        }

        $pageTitle = 'Dashboard — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'dashboard/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }
}

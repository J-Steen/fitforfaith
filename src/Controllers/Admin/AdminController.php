<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Leaderboard;
use App\Models\Donation;
use App\Models\User;
use App\Models\Church;

class AdminController {
    public function dashboard(array $params): void {
        Auth::requireAdmin();
        $stats        = Leaderboard::getStats();
        $totalRaised  = Donation::totalRaisedCents();
        $recentUsers  = \App\Core\Database::fetchAll(
            'SELECT u.*, c.name AS church_name FROM users u LEFT JOIN churches c ON c.id = u.church_id
             WHERE u.deleted_at IS NULL ORDER BY u.created_at DESC LIMIT 10'
        );
        $topChurches  = Leaderboard::getChurch(5);
        $topUsers     = Leaderboard::getIndividual(5);

        $pageTitle = 'Admin Dashboard — ' . APP_NAME;
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/dashboard.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }
}

<?php
namespace App\Controllers;

use App\Models\Leaderboard;
use App\Models\Donation;
use App\Core\Cache;

class HomeController {
    public function index(array $params): void {
        $stats        = Leaderboard::getStats();
        $topUsers     = Leaderboard::getIndividual(5);
        $topChurches  = Leaderboard::getChurch(5);
        $leadingChurch= Leaderboard::getLeadingChurch();
        $totalRaised  = Donation::totalRaisedCents();

        $pageTitle = APP_NAME . ' — ' . APP_TAGLINE;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'home/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }
}

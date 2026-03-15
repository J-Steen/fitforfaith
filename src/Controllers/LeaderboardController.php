<?php
namespace App\Controllers;

use App\Models\Leaderboard;
use App\Core\Cache;

class LeaderboardController {
    public function index(array $params): void {
        $tab = $_GET['tab'] ?? 'individual';

        $individuals  = Cache::remember('leaderboard_individual', CACHE_LEADERBOARD,
            fn() => Leaderboard::getIndividualAll()
        );
        $churches     = Cache::remember('leaderboard_church', CACHE_LEADERBOARD,
            fn() => Leaderboard::getChurch(50)
        );
        $leadingChurch = Leaderboard::getLeadingChurch();
        $stats         = Leaderboard::getStats();

        $pageTitle = 'Leaderboard — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'leaderboard/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }
}

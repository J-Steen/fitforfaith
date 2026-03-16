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
        $runners  = Cache::remember('leaderboard_run',  CACHE_LEADERBOARD, fn() => Leaderboard::getByActivity('run'));
        $walkers  = Cache::remember('leaderboard_walk', CACHE_LEADERBOARD, fn() => Leaderboard::getByActivity('walk'));
        $cyclists = Cache::remember('leaderboard_ride', CACHE_LEADERBOARD, fn() => Leaderboard::getByActivity('ride'));

        $leadingChurch = Leaderboard::getLeadingChurch();
        $stats         = Leaderboard::getStats();

        $pageTitle = 'Leaderboard — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'leaderboard/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }
}

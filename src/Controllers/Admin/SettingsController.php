<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Core\Cache;
use App\Services\PointsService;

class SettingsController {
    public function index(array $params): void {
        Auth::requireAdmin();
        $settings  = Database::fetchAll('SELECT * FROM settings ORDER BY `key` ASC');
        $settingsMap = array_column($settings, 'value', 'key');
        $pageTitle = 'Settings — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/settings/index.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function update(array $params): void {
        Auth::requireAdmin();

        $keys = [
            'points_per_km_run', 'points_per_km_walk', 'points_per_km_ride',
            'max_points_per_day', 'event_start_date', 'event_end_date',
            'registration_open', 'registration_fee', 'site_name', 'site_tagline',
        ];

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                Database::execute(
                    'INSERT INTO settings (`key`, `value`) VALUES (?,?)
                     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
                    [$key, trim($_POST[$key])]
                );
            }
        }

        // Bust caches
        Cache::flush();
        PointsService::invalidateCache();

        Session::flash('success', 'Settings saved successfully.');
        redirect('admin/settings');
    }
}

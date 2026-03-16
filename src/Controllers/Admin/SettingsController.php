<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Core\Cache;
use App\Services\PointsService;
use App\Models\User;

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

    public function changePassword(array $params): void {
        Auth::requireAdmin();

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = Auth::user();

        if (!password_verify($current, $user['password_hash'])) {
            Session::flash('error', 'Current password is incorrect.');
            redirect('admin/settings');
        }
        if (strlen($new) < 8) {
            Session::flash('error', 'New password must be at least 8 characters.');
            redirect('admin/settings');
        }
        if ($new !== $confirm) {
            Session::flash('error', 'New passwords do not match.');
            redirect('admin/settings');
        }

        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, (int)$user['id']]);

        Session::flash('success', 'Password changed successfully.');
        redirect('admin/settings');
    }
}

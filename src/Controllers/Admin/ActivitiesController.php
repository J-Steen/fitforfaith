<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Models\Activity;

class ActivitiesController {
    public function index(array $params): void {
        Auth::requireAdmin();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'flagged' => isset($_GET['flagged']) ? 1 : null,
            'type'    => $_GET['type'] ?? '',
        ];
        $result    = Activity::getAllPaginated($page, 50, $filters);
        $pageTitle = 'Activities — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/activities/index.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function flag(array $params): void {
        Auth::requireAdmin();
        Activity::flag((int)($params['id'] ?? 0));
        Session::flash('success', 'Activity flagged.');
        $back = $_POST['_redirect'] ?? '';
        if ($back) { header('Location: ' . $back); exit; }
        redirect('admin/activities');
    }

    public function unflag(array $params): void {
        Auth::requireAdmin();
        Activity::unflag((int)($params['id'] ?? 0));
        Session::flash('success', 'Activity unflagged.');
        $back = $_POST['_redirect'] ?? '';
        if ($back) { header('Location: ' . $back); exit; }
        redirect('admin/activities');
    }
}

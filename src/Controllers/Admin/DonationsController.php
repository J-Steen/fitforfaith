<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Donation;

class DonationsController {
    public function index(array $params): void {
        Auth::requireAdmin();
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $result   = Donation::getAllPaginated($page, 50);
        $total    = Donation::totalRaisedCents();
        $pageTitle = 'Payments — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/donations/index.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }
}

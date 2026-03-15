<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Church;
use App\Models\QRCode;

class ChurchesController {
    public function index(array $params): void {
        Auth::requireAdmin();
        $churches  = Church::allWithStats();
        $qrCodes   = QRCode::allForAdmin();
        $pageTitle = 'Manage Churches — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/churches/index.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function create(array $params): void {
        Auth::requireAdmin();
        $pageTitle = 'Add Church — Admin';
        $errors = Session::getFlash('errors') ?? [];
        $old    = Session::getFlash('old') ?? [];
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/churches/edit.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function store(array $params): void {
        Auth::requireAdmin();
        $v = Validator::make($_POST, [
            'name' => 'required|min:2|max:120|unique:churches:name',
            'city' => 'max:80',
        ]);
        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            Session::flash('old', $_POST);
            redirect('admin/churches/new');
        }
        $id = Church::create([
            'name'        => $_POST['name'],
            'city'        => $_POST['city'] ?? null,
            'description' => $_POST['description'] ?? null,
            'is_active'   => 1,
        ]);
        Session::flash('success', 'Church created successfully.');
        redirect('admin/churches');
    }

    public function edit(array $params): void {
        Auth::requireAdmin();
        $church = Church::findById((int)($params['id'] ?? 0));
        if (!$church) { http_response_code(404); exit; }
        $errors = Session::getFlash('errors') ?? [];
        $pageTitle = 'Edit Church — Admin';
        include VIEW_PATH . 'layout/admin_base.php';
        include VIEW_PATH . 'admin/churches/edit.php';
        include VIEW_PATH . 'layout/admin_footer.php';
    }

    public function update(array $params): void {
        Auth::requireAdmin();
        $id     = (int)($params['id'] ?? 0);
        $church = Church::findById($id);
        if (!$church) redirect('admin/churches');

        $v = Validator::make($_POST, [
            'name' => 'required|min:2|max:120',
            'city' => 'max:80',
        ]);
        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            redirect('admin/churches/' . $id . '/edit');
        }
        Church::update($id, [
            'name'        => $_POST['name'],
            'slug'        => slugify($_POST['name']),
            'city'        => $_POST['city'] ?? null,
            'description' => $_POST['description'] ?? null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ]);
        Session::flash('success', 'Church updated.');
        redirect('admin/churches');
    }

    public function delete(array $params): void {
        Auth::requireAdmin();
        $id = (int)($params['id'] ?? 0);
        Church::delete($id);
        Session::flash('success', 'Church deleted.');
        redirect('admin/churches');
    }

    public function createQR(array $params): void {
        Auth::requireAdmin();
        $churchId = (int)($_POST['church_id'] ?? 0) ?: null;
        $label    = trim($_POST['label'] ?? '');
        if (!$label) {
            $label = $churchId ? (Church::findById($churchId)['name'] ?? 'Church') . ' QR' : 'General QR';
        }
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $token     = QRCode::create($label, $churchId, Auth::id(), $expiresAt);
        Session::flash('success', 'QR code created. Token: ' . $token);
        redirect('admin/churches');
    }
}

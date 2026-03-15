<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\QRCode;
use App\Services\QRCodeService;

class QRController {
    public function serveImage(array $params): void {
        $token = $params['token'] ?? '';
        QRCodeService::serve($token);
    }

    /**
     * QR landing page — store token in session, redirect to register with church preselected.
     */
    public function landingPage(array $params): void {
        $token = $params['token'] ?? '';
        $qr    = QRCode::findByToken($token);

        if (!$qr) {
            Session::flash('error', 'This QR code is no longer active.');
            redirect('register');
        }

        QRCode::incrementScans($token);
        Session::set('qr_token', $token);

        if (Auth::check()) {
            redirect('dashboard');
        }

        redirect('register');
    }
}

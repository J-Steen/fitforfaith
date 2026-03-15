<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\Donation;
use App\Models\User;
use App\Services\PayFastService;

class DonationController {
    public function form(array $params): void {
        Auth::require();
        $user = Auth::user();
        $existing = Donation::getLatestForUser((int)$user['id']);
        $pageTitle = 'Registration Fee — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'donation/form.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function initiate(array $params): void {
        Auth::require();
        $user = Auth::user();

        // Check if already paid
        if ($user['is_paid']) {
            Session::flash('info', 'Your registration fee is already paid.');
            redirect('dashboard');
        }

        $churchId   = (int)($user['church_id'] ?? 0);
        $donationId = Donation::createPending((int)$user['id'], $churchId, REGISTRATION_FEE_CENTS);

        // Render auto-submit PayFast form
        $pageTitle = 'Redirecting to PayFast…';
        $formHtml  = PayFastService::renderAutoSubmitForm($donationId, REGISTRATION_FEE_CENTS, $user);

        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'donation/redirect.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function return(array $params): void {
        // PayFast return URL — do NOT use this to confirm payment
        // Payment confirmation comes via ITN (itn method below)
        Auth::require();
        $user = Auth::user();
        $pageTitle = 'Payment Processing — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'donation/thankyou.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function cancel(array $params): void {
        Session::flash('info', 'Payment was cancelled.');
        if (Auth::check()) redirect('donate');
        redirect('register');
    }

    /**
     * PayFast ITN handler.
     * PayFast sends a server-side POST here to confirm payment.
     * NO CSRF check — this endpoint uses PayFast signature verification.
     */
    public function itn(array $params): void {
        // Respond quickly
        header('HTTP/1.0 200 OK');
        ob_start();

        app_log('PayFast ITN received from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        $postData = $_POST;
        if (empty($postData)) {
            app_log('PayFast ITN: empty POST data', 'WARN');
            exit;
        }

        // Verify ITN
        if (!PayFastService::verifyITN($postData)) {
            app_log('PayFast ITN: verification failed', 'ERROR');
            exit;
        }

        $paymentStatus = strtolower($postData['payment_status'] ?? '');
        $pfPaymentId   = $postData['pf_payment_id'] ?? '';
        $donationId    = (int)($postData['m_payment_id'] ?? 0);
        $amountGross   = (float)($postData['amount_gross'] ?? 0);

        if (!$donationId) {
            app_log('PayFast ITN: missing m_payment_id', 'WARN');
            exit;
        }

        $donation = Donation::findById($donationId);
        if (!$donation) {
            app_log("PayFast ITN: donation $donationId not found", 'WARN');
            exit;
        }

        // Step 5: Verify amount (within 5 cents to handle rounding)
        $expectedCents = (int)$donation['amount_cents'];
        $receivedCents = (int)round($amountGross * 100);
        if (abs($expectedCents - $receivedCents) > 5) {
            app_log("PayFast ITN: amount mismatch. Expected $expectedCents, got $receivedCents", 'ERROR');
            exit;
        }

        if ($paymentStatus === 'complete') {
            Donation::markComplete($donationId, $pfPaymentId, json_encode($postData));
            // Mark user as paid
            if ($donation['user_id']) {
                User::markPaid((int)$donation['user_id']);
                app_log("User {$donation['user_id']} marked as paid via PayFast ITN $pfPaymentId");
            }
        } elseif (in_array($paymentStatus, ['failed', 'cancelled'])) {
            Donation::markFailed($donationId, $pfPaymentId);
        }

        ob_end_flush();
        exit;
    }
}

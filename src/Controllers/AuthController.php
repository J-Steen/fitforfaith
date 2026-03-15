<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Church;
use App\Models\QRCode;
use App\Models\User;
use App\Services\MailService;

class AuthController {
    public function showRegister(array $params): void {
        if (Auth::check()) redirect('dashboard');

        $churches = Church::all();
        $preselectedChurch = null;

        // Handle QR code preselection
        $token = $params['token'] ?? null;
        if ($token) {
            $qr = QRCode::findByToken($token);
            if ($qr) {
                QRCode::incrementScans($token);
                Session::set('qr_token', $token);
                if ($qr['church_id']) $preselectedChurch = (int)$qr['church_id'];
            }
        } else {
            $token = Session::get('qr_token');
            if ($token) {
                $qr = QRCode::findByToken($token);
                if ($qr && $qr['church_id']) $preselectedChurch = (int)$qr['church_id'];
            }
        }

        $pageTitle = 'Register — ' . APP_NAME;
        $old = Session::getFlash('old') ?? [];
        $errors = Session::getFlash('errors') ?? [];
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'auth/register.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function register(array $params): void {
        if (Auth::check()) redirect('dashboard');

        $v = Validator::make($_POST, [
            'first_name'           => 'required|min:2|max:60',
            'last_name'            => 'required|min:2|max:60',
            'email'                => 'required|email|unique:users:email',
            'password'             => 'required|min:8|confirmed',
            'church_id'            => 'required|integer',
            'terms'                => 'required',
        ]);

        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            Session::flash('old', $_POST);
            redirect('register');
        }

        $platform = $_POST['fitness_platform'] ?? null;
        if ($platform && !array_key_exists($platform, \App\Models\User::PLATFORMS)) $platform = null;

        $language = $_POST['language'] ?? 'en';
        if (!in_array($language, \App\Core\Lang::LOCALES)) $language = 'en';

        $userId = User::create([
            'first_name'       => $_POST['first_name'],
            'last_name'        => $_POST['last_name'],
            'email'            => $_POST['email'],
            'password'         => $_POST['password'],
            'church_id'        => (int)$_POST['church_id'],
            'phone'            => $_POST['phone'] ?? null,
            'fitness_platform' => $platform,
            'registration_ref' => Session::get('qr_token'),
            'language'         => $language,
        ]);

        Session::set('locale', $language);
        \App\Core\Lang::set($language);

        Session::forget('qr_token');

        $user = User::findById($userId);
        Auth::loginUser($user);

        // Send email verification
        try {
            $emailToken  = User::generateEmailToken($userId);
            $verifyUrl   = APP_URL . '/verify-email/' . $emailToken;
            MailService::sendEmailVerification($user['email'], $user['first_name'], $verifyUrl);
        } catch (\Throwable $e) {
            app_log('Email verification send failed: ' . $e->getMessage(), 'WARN');
        }

        Session::flash('success', 'Welcome to ' . APP_NAME . '! Check your email to verify your address.');
        redirect('dashboard');
    }

    public function showLogin(array $params): void {
        if (Auth::check()) redirect('dashboard');
        $pageTitle = 'Login — ' . APP_NAME;
        $errors = Session::getFlash('errors') ?? [];
        $old = Session::getFlash('old') ?? [];
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'auth/login.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function login(array $params): void {
        if (Auth::check()) redirect('dashboard');

        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        // Simple rate limiting via session
        $attempts = Session::get('login_attempts', 0);
        $lastAttempt = Session::get('login_last_attempt', 0);
        if ($attempts >= 5 && (time() - $lastAttempt) < 900) {
            Session::flash('errors', ['email' => 'Too many login attempts. Please wait 15 minutes.']);
            redirect('login');
        }

        $user = Auth::attempt($email, $password);
        if (!$user) {
            Session::set('login_attempts', $attempts + 1);
            Session::set('login_last_attempt', time());
            Session::flash('errors', ['email' => 'Invalid email or password.']);
            Session::flash('old', ['email' => $email]);
            redirect('login');
        }

        Session::set('login_attempts', 0);
        redirect('dashboard');
    }

    public function logout(array $params): void {
        Auth::logout();
        redirect('login');
    }

    public function showForgot(array $params): void {
        $pageTitle = 'Forgot Password — ' . APP_NAME;
        $errors = Session::getFlash('errors') ?? [];
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'auth/forgot.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function forgot(array $params): void {
        // Rate limit: max 3 attempts per 15 minutes
        $attempts    = Session::get('forgot_attempts', 0);
        $lastAttempt = Session::get('forgot_last_attempt', 0);
        if ($attempts >= 3 && (time() - $lastAttempt) < 900) {
            Session::flash('error', 'Too many reset attempts. Please wait 15 minutes before trying again.');
            redirect('forgot-password');
        }
        Session::set('forgot_attempts', $attempts + 1);
        Session::set('forgot_last_attempt', time());

        $email = strtolower(trim($_POST['email'] ?? ''));
        $user  = User::findByEmail($email);
        // Always show success to prevent email enumeration
        if ($user) {
            $token    = User::setResetToken((int)$user['id']);
            $resetUrl = APP_URL . '/reset-password/' . $token;
            try {
                MailService::sendPasswordReset($user['email'], $user['first_name'], $resetUrl);
                app_log("Password reset email sent for user {$user['id']}", 'INFO');
            } catch (\Throwable $e) {
                app_log("Password reset email failed for user {$user['id']}: " . $e->getMessage(), 'WARN');
            }
        }
        Session::flash('success', 'If that email is registered, you will receive a reset link shortly.');
        redirect('forgot-password');
    }

    public function resendVerification(array $params): void {
        Auth::require();
        $user = Auth::user();
        if (!empty($user['email_verified_at'])) {
            Session::flash('info', 'Your email is already verified.');
            redirect('dashboard');
        }
        try {
            $token     = User::generateEmailToken((int)$user['id']);
            $verifyUrl = APP_URL . '/verify-email/' . $token;
            MailService::sendEmailVerification($user['email'], $user['first_name'], $verifyUrl);
            Session::flash('success', 'Verification email resent. Please check your inbox.');
        } catch (\Throwable $e) {
            app_log('Resend verification failed: ' . $e->getMessage(), 'WARN');
            Session::flash('error', 'Could not send verification email. Please contact support.');
        }
        redirect('dashboard');
    }

    public function verifyEmail(array $params): void {
        $token = $params['token'] ?? '';
        $user  = User::findByEmailToken($token);
        if (!$user) {
            Session::flash('error', 'This verification link is invalid or has already been used.');
            redirect('dashboard');
        }
        User::verifyEmail((int)$user['id']);
        // Refresh session if this is the logged-in user
        if (Auth::check() && Auth::user()['id'] == $user['id']) {
            Auth::refreshUser();
        }
        Session::flash('success', 'Your email address has been verified. Thank you!');
        redirect('dashboard');
    }

    public function showReset(array $params): void {
        $token = $params['token'] ?? '';
        $user  = User::findByResetToken($token);
        if (!$user) {
            Session::flash('error', 'This password reset link has expired or is invalid.');
            redirect('login');
        }
        $pageTitle = 'Reset Password — ' . APP_NAME;
        $errors = Session::getFlash('errors') ?? [];
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'auth/reset.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function reset(array $params): void {
        $token = $params['token'] ?? '';
        $v = Validator::make($_POST, [
            'password' => 'required|min:8|confirmed',
        ]);
        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            redirect('reset-password/' . $token);
        }
        $ok = User::consumeResetToken($token, $_POST['password']);
        if (!$ok) {
            Session::flash('error', 'Invalid or expired reset token.');
            redirect('login');
        }
        Session::flash('success', 'Password updated. Please log in.');
        redirect('login');
    }
}

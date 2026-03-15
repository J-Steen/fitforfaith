<?php
namespace App\Core;

use App\Models\User;

class Auth {
    private static ?array $currentUser = null;

    /**
     * Attempt to log a user in by credentials. Returns user array or null.
     */
    public static function attempt(string $email, string $password): ?array {
        $user = User::findByEmail($email);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        if (!$user['is_active'] || $user['deleted_at']) return null;
        self::loginUser($user);
        return $user;
    }

    /**
     * Log a user in directly (after registration, social auth, etc).
     */
    public static function loginUser(array $user): void {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        self::$currentUser = $user;
    }

    /**
     * Log the current user out.
     */
    public static function logout(): void {
        Session::destroy();
        self::$currentUser = null;
    }

    /**
     * Get the currently authenticated user array (cached per request).
     */
    public static function user(): ?array {
        if (self::$currentUser !== null) return self::$currentUser;
        $id = Session::get('user_id');
        if (!$id) return null;
        $user = User::findById((int)$id);
        if (!$user || !$user['is_active'] || $user['deleted_at']) {
            self::logout();
            return null;
        }
        self::$currentUser = $user;
        return $user;
    }

    /**
     * Check if there is an authenticated user.
     */
    public static function check(): bool {
        return self::user() !== null;
    }

    /**
     * Require authentication — redirect to login if not authenticated.
     */
    public static function require(): void {
        if (!self::check()) {
            Session::flash('error', 'Please log in to continue.');
            redirect('login');
        }
    }

    /**
     * Require admin role — show 403 if not admin.
     */
    public static function requireAdmin(): void {
        self::require();
        if (self::user()['role'] !== 'admin') {
            http_response_code(403);
            include VIEW_PATH . 'errors/403.php';
            exit;
        }
    }

    /**
     * Hash a password.
     */
    public static function hashPassword(string $plain): string {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a password against a hash.
     */
    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    /**
     * Get current user ID.
     */
    public static function id(): ?int {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    /**
     * Check if current user is admin.
     */
    public static function isAdmin(): bool {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }
}

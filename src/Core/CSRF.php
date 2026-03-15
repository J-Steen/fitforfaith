<?php
namespace App\Core;

class CSRF {
    private const KEY = '_csrf_token';

    public static function generate(): string {
        if (!Session::has(self::KEY)) {
            Session::set(self::KEY, bin2hex(random_bytes(32)));
        }
        return Session::get(self::KEY);
    }

    public static function token(): string {
        return self::generate();
    }

    public static function verify(?string $token): bool {
        $stored = Session::get(self::KEY);
        if (!$stored || !$token) return false;
        $valid = hash_equals($stored, $token);
        // Rotate token after use
        if ($valid) {
            Session::set(self::KEY, bin2hex(random_bytes(32)));
        }
        return $valid;
    }

    /**
     * Returns an HTML hidden input field.
     */
    public static function field(): string {
        return '<input type="hidden" name="_csrf" value="' . h(self::generate()) . '">';
    }

    /**
     * Verify from POST data or abort with 419.
     */
    public static function verifyRequest(): void {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!self::verify($token)) {
            http_response_code(419);
            die('CSRF token mismatch. Please go back and try again.');
        }
    }
}

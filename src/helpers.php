<?php
/**
 * Global helper functions available everywhere.
 */

/**
 * Escape HTML output — use in every view.
 */
function h(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirect to URL and exit.
 */
function redirect(string $path, int $code = 302): never {
    $url = str_starts_with($path, 'http') ? $path : APP_URL . '/' . ltrim($path, '/');
    header('Location: ' . $url, true, $code);
    exit;
}

/**
 * Return absolute URL for a path.
 */
function url(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Return asset URL.
 */
function asset(string $path): string {
    return APP_URL . '/public/' . ltrim($path, '/');
}

/**
 * Format points with thousands separator.
 */
function fmt_points(int $pts): string {
    return number_format($pts);
}

/**
 * Format distance in km.
 */
function fmt_km(float $meters): string {
    return number_format($meters / 1000, 2) . ' km';
}

/**
 * Format duration seconds as H:MM:SS or MM:SS.
 */
function fmt_duration(int $seconds): string {
    $h = (int)($seconds / 3600);
    $m = (int)(($seconds % 3600) / 60);
    $s = $seconds % 60;
    if ($h > 0) return sprintf('%d:%02d:%02d', $h, $m, $s);
    return sprintf('%d:%02d', $m, $s);
}

/**
 * Format cents to ZAR display.
 */
function fmt_money(int $cents): string {
    return 'R' . number_format($cents / 100, 2);
}

/**
 * Ordinal suffix — 1st, 2nd, 3rd…
 */
function ordinal(int $n): string {
    $suffix = ['th','st','nd','rd'];
    $v = $n % 100;
    return $n . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
}

/**
 * Activity type → human-readable label.
 */
function activity_label(string $type): string {
    return match(strtolower($type)) {
        'run'        => 'Run',
        'walk'       => 'Walk',
        'ride'       => 'Cycling',
        'virtualride'=> 'Virtual Ride',
        'hike'       => 'Hike',
        'workout'    => 'Workout',
        default      => ucfirst($type),
    };
}

/**
 * Activity type → Font Awesome icon HTML.
 */
function activity_icon(string $type): string {
    return match(strtolower($type)) {
        'run'         => '<i class="fa-solid fa-person-running"></i>',
        'walk'        => '<i class="fa-solid fa-person-walking"></i>',
        'ride',
        'virtualride' => '<i class="fa-solid fa-person-biking"></i>',
        'hike'        => '<i class="fa-solid fa-person-hiking"></i>',
        'workout'     => '<i class="fa-solid fa-dumbbell"></i>',
        default       => '<i class="fa-solid fa-bolt"></i>',
    };
}

/**
 * Flash message shorthand (uses Session).
 */
function flash(string $type, string $message): void {
    \App\Core\Session::flash($type, $message);
}

/**
 * Get flash message.
 */
function get_flash(string $type): ?string {
    return \App\Core\Session::getFlash($type);
}

/**
 * Generate a cryptographically secure token.
 */
function generate_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

/**
 * Slugify a string.
 */
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Truncate text to N characters.
 */
function truncate(string $text, int $length = 60): string {
    return mb_strlen($text) > $length
        ? mb_substr($text, 0, $length) . '…'
        : $text;
}

/**
 * Check if user is logged in (shorthand).
 */
function auth_check(): bool {
    return \App\Core\Auth::check();
}

/**
 * Get current user (shorthand).
 */
function auth_user(): ?array {
    return \App\Core\Auth::user();
}

/**
 * Check if current user is admin.
 */
function is_admin(): bool {
    $user = auth_user();
    return $user && $user['role'] === 'admin';
}

/**
 * CSRF hidden field HTML.
 */
function csrf_field(): string {
    return \App\Core\CSRF::field();
}

/**
 * Format a MySQL datetime as relative time.
 */
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return (int)($diff/60) . ' min ago';
    if ($diff < 86400) return (int)($diff/3600) . ' hrs ago';
    if ($diff < 604800) return (int)($diff/86400) . ' days ago';
    return date('j M Y', strtotime($datetime));
}

/**
 * Medal icon for rank.
 */
function rank_medal(int $rank): string {
    return match($rank) {
        1 => '<i class="fa-solid fa-medal" style="color:#FFD700"></i>',
        2 => '<i class="fa-solid fa-medal" style="color:#C0C0C0"></i>',
        3 => '<i class="fa-solid fa-medal" style="color:#CD7F32"></i>',
        default => '#' . $rank,
    };
}

/**
 * Translate a string key.
 */
function t(string $key, array $replace = []): string {
    return \App\Core\Lang::t($key, $replace);
}

/**
 * Log application message.
 */
function app_log(string $message, string $level = 'INFO'): void {
    $line = date('Y-m-d H:i:s') . " [$level] $message\n";
    if (is_dir(LOG_PATH)) {
        file_put_contents(LOG_PATH . '/app.log', $line, FILE_APPEND | LOCK_EX);
    }
}

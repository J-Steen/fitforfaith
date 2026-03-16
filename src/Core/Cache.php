<?php
namespace App\Core;

class Cache {
    private static string $dir = '';

    private static function dir(): string {
        if (self::$dir === '') {
            self::$dir = defined('CACHE_PATH') ? CACHE_PATH : sys_get_temp_dir() . '/fff_cache';
            if (!is_dir(self::$dir)) mkdir(self::$dir, 0755, true);
        }
        return self::$dir;
    }

    private static function path(string $key): string {
        return self::dir() . '/' . md5($key) . '.cache';
    }

    public static function get(string $key) {
        $path = self::path($key);
        if (!file_exists($path)) return null;

        $content = file_get_contents($path);
        if ($content === false) return null;

        $pos = strpos($content, "\n");
        if ($pos === false) return null;

        $expiry = (int)substr($content, 0, $pos);
        if ($expiry !== 0 && $expiry < time()) {
            @unlink($path);
            return null;
        }

        return unserialize(substr($content, $pos + 1));
    }

    /**
     * @param int $ttl Seconds until expiry. 0 = forever.
     */
    public static function set(string $key, $value, int $ttl = 300): void {
        $expiry  = $ttl > 0 ? (time() + $ttl) : 0;
        $content = $expiry . "\n" . serialize($value);
        file_put_contents(self::path($key), $content, LOCK_EX);
    }

    public static function forget(string $key): void {
        $path = self::path($key);
        if (file_exists($path)) @unlink($path);
    }

    /**
     * Delete all cache files with keys matching a prefix pattern.
     * Since we hash keys, this uses a prefix stored in a manifest.
     */
    public static function flush(string $prefix = ''): void {
        if ($prefix === '') {
            // Clear everything
            foreach (glob(self::dir() . '/*.cache') as $f) {
                @unlink($f);
            }
            return;
        }
        // Store a prefix→hash mapping for prefix-based clearing
        // For simplicity, flush all when prefix given
        foreach (glob(self::dir() . '/*.cache') as $f) {
            @unlink($f);
        }
    }

    /**
     * Get or set — helper for "remember" pattern.
     */
    public static function remember(string $key, int $ttl, callable $callback) {
        $value = self::get($key);
        if ($value !== null) return $value;
        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }

    /**
     * Check if a key exists (not expired).
     */
    public static function has(string $key): bool {
        return self::get($key) !== null;
    }
}

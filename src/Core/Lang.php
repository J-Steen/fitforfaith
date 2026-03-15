<?php
namespace App\Core;

class Lang {
    private static string $locale = 'en';
    private static array  $strings = [];
    private static bool   $loaded  = false;

    const LOCALES = ['en', 'af'];
    const LABELS  = ['en' => 'English', 'af' => 'Afrikaans'];

    /** Boot after Session::start() and Database::init() */
    public static function boot(): void {
        // Logged-in user preference takes priority
        $user = Auth::user();
        if ($user && !empty($user['language']) && in_array($user['language'], self::LOCALES)) {
            self::set($user['language']);
            return;
        }
        // Fall back to session locale
        $saved = Session::get('locale', 'en');
        self::set(in_array($saved, self::LOCALES) ? $saved : 'en');
    }

    public static function set(string $locale): void {
        $locale = in_array($locale, self::LOCALES) ? $locale : 'en';
        self::$locale = $locale;
        self::$loaded = false;
        self::load();
    }

    public static function get(): string {
        return self::$locale;
    }

    public static function t(string $key, array $replace = []): string {
        if (!self::$loaded) self::load();
        $str = self::$strings[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $str = str_replace(':' . $k, (string)$v, $str);
        }
        return $str;
    }

    private static function load(): void {
        if (self::$loaded) return;
        $file = BASE_PATH . '/lang/' . self::$locale . '.php';
        self::$strings = file_exists($file) ? require $file : [];
        self::$loaded  = true;
    }
}

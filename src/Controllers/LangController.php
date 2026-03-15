<?php
namespace App\Controllers;

use App\Core\Lang;
use App\Core\Session;
use App\Core\Auth;
use App\Models\User;

class LangController {
    public function set(array $params): void {
        $locale = $params['locale'] ?? 'en';
        if (!in_array($locale, Lang::LOCALES)) $locale = 'en';

        Lang::set($locale);
        Session::set('locale', $locale);

        // Persist to user record if logged in
        if (Auth::check()) {
            $user = Auth::user();
            User::update((int)$user['id'], ['language' => $locale]);
        }

        // Redirect back
        $ref = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $ref, true, 302);
        exit;
    }
}

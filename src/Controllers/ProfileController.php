<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Lang;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Church;
use App\Models\User;

class ProfileController {
    public function edit(array $params): void {
        Auth::require();
        $user      = Auth::user();
        $churches  = Church::all();
        $pageTitle = 'My Profile — ' . APP_NAME;
        $errors         = Session::getFlash('errors') ?? [];
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'profile/edit.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function update(array $params): void {
        Auth::require();
        $user = Auth::user();

        $v = Validator::make($_POST, [
            'first_name' => 'required|min:2|max:60',
            'last_name'  => 'required|min:2|max:60',
            'church_id'  => 'required|integer',
            'phone'      => 'max:20',
        ]);

        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            redirect('profile/edit');
        }

        $platform = $_POST['fitness_platform'] ?? null;
        if ($platform && !array_key_exists($platform, \App\Models\User::PLATFORMS)) $platform = null;

        $language = $_POST['language'] ?? 'en';
        if (!in_array($language, Lang::LOCALES)) $language = 'en';

        User::update((int)$user['id'], [
            'first_name'       => $_POST['first_name'],
            'last_name'        => $_POST['last_name'],
            'church_id'        => (int)$_POST['church_id'] ?: null,
            'phone'            => $_POST['phone'] ?? null,
            'fitness_platform' => $platform,
            'language'         => $language,
        ]);

        // Apply language preference immediately
        Lang::set($language);
        Session::set('locale', $language);

        Session::flash('success', 'Profile updated successfully.');
        redirect('profile/edit');
    }

    public function changePassword(array $params): void {
        Auth::require();
        $user = Auth::user();

        $v = Validator::make($_POST, [
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            redirect('profile/edit');
        }

        if (!Auth::verifyPassword($_POST['current_password'], $user['password_hash'])) {
            Session::flash('errors', ['current_password' => 'Current password is incorrect.']);
            redirect('profile/edit');
        }

        User::updatePassword((int)$user['id'], $_POST['password']);
        Session::flash('success', 'Password changed successfully.');
        redirect('profile/edit');
    }
}

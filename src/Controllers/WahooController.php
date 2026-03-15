<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\PlatformToken;
use App\Models\PlatformWebhook;
use App\Models\User;
use App\Services\WahooService;

class WahooController {
    public function connect(array $params): void {
        Auth::require();
        $state = bin2hex(random_bytes(16));
        Session::set('wahoo_state', $state);
        redirect(WahooService::getAuthUrl($state));
    }

    public function callback(array $params): void {
        Auth::require();
        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';

        if (!$code || $state !== Session::get('wahoo_state')) {
            Session::flash('error', 'Wahoo authorisation failed. Please try again.');
            redirect('profile/edit');
        }
        Session::forget('wahoo_state');

        $user   = Auth::user();
        $tokens = WahooService::exchangeCode($code);

        // Get Wahoo user ID
        try {
            $wahooUser = WahooService::getUser($tokens['access_token']);
            $tokens['platform_user_id'] = (string)($wahooUser['id'] ?? '');
        } catch (\Throwable $e) {}

        PlatformToken::store((int)$user['id'], 'wahoo', $tokens);
        User::update((int)$user['id'], ['fitness_platform' => 'wahoo']);
        Auth::refreshUser();

        PlatformWebhook::queue('wahoo', 'backfill', $tokens['platform_user_id'] ?? '', null, null);

        Session::flash('success', 'Wahoo connected! Your activities will sync shortly.');
        redirect('dashboard');
    }

    public function disconnect(array $params): void {
        Auth::require();
        $user = Auth::user();
        PlatformToken::delete((int)$user['id'], 'wahoo');
        User::update((int)$user['id'], ['fitness_platform' => null]);
        Auth::refreshUser();
        Session::flash('success', 'Wahoo disconnected.');
        redirect('profile/edit');
    }

    public function webhookReceive(array $params): void {
        http_response_code(200);
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];
        if (!empty($data['workout'])) {
            $w = $data['workout'];
            PlatformWebhook::queue('wahoo', 'create',
                (string)($data['user']['id'] ?? ''),
                (string)($w['id'] ?? ''),
                $raw
            );
        }
        exit;
    }
}

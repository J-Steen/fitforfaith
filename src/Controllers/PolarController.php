<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\PlatformToken;
use App\Models\PlatformWebhook;
use App\Models\User;
use App\Services\PolarService;

class PolarController {
    public function connect(array $params): void {
        Auth::require();
        $state = bin2hex(random_bytes(16));
        Session::set('polar_state', $state);
        redirect(PolarService::getAuthUrl($state));
    }

    public function callback(array $params): void {
        Auth::require();
        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';

        if (!$code || $state !== Session::get('polar_state')) {
            Session::flash('error', 'Polar authorisation failed. Please try again.');
            redirect('profile/edit');
        }
        Session::forget('polar_state');

        $user   = Auth::user();
        $tokens = PolarService::exchangeCode($code);

        PlatformToken::store((int)$user['id'], 'polar', $tokens);
        User::update((int)$user['id'], ['fitness_platform' => 'polar']);
        Auth::refreshUser();

        // Register user with Polar Accesslink (required once)
        try {
            PolarService::registerUser($tokens['access_token'], $tokens['platform_user_id'] ?? '');
        } catch (\Throwable $e) {
            app_log('Polar register user error: ' . $e->getMessage(), 'WARN');
        }

        PlatformWebhook::queue('polar', 'backfill', $tokens['platform_user_id'], null, null);

        Session::flash('success', 'Polar connected! Your activities will sync shortly.');
        redirect('dashboard');
    }

    public function disconnect(array $params): void {
        Auth::require();
        $user = Auth::user();
        PlatformToken::delete((int)$user['id'], 'polar');
        User::update((int)$user['id'], ['fitness_platform' => null]);
        Auth::refreshUser();
        Session::flash('success', 'Polar disconnected.');
        redirect('profile/edit');
    }

    /** Polar sends exercise notifications here. */
    public function webhookReceive(array $params): void {
        http_response_code(200);
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];
        if (($data['event'] ?? '') === 'EXERCISE') {
            PlatformWebhook::queue(
                'polar', 'create',
                (string)($data['user_id'] ?? ''),
                (string)($data['entity_id'] ?? ''),
                $raw
            );
        }
        exit;
    }
}

<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\PlatformToken;
use App\Models\PlatformWebhook;
use App\Models\User;
use App\Services\SuuntoService;

class SuuntoController {
    public function connect(array $params): void {
        Auth::require();
        $state = bin2hex(random_bytes(16));
        Session::set('suunto_state', $state);
        redirect(SuuntoService::getAuthUrl($state));
    }

    public function callback(array $params): void {
        Auth::require();
        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';

        if (!$code || $state !== Session::get('suunto_state')) {
            Session::flash('error', 'Suunto authorisation failed. Please try again.');
            redirect('profile/edit');
        }
        Session::forget('suunto_state');

        $user   = Auth::user();
        $tokens = SuuntoService::exchangeCode($code);

        PlatformToken::store((int)$user['id'], 'suunto', $tokens);
        User::update((int)$user['id'], ['fitness_platform' => 'suunto']);
        Auth::refreshUser();

        PlatformWebhook::queue('suunto', 'backfill', $tokens['platform_user_id'] ?? '', null, null);

        Session::flash('success', 'Suunto connected! Your activities will sync shortly.');
        redirect('dashboard');
    }

    public function disconnect(array $params): void {
        Auth::require();
        $user = Auth::user();
        PlatformToken::delete((int)$user['id'], 'suunto');
        User::update((int)$user['id'], ['fitness_platform' => null]);
        Auth::refreshUser();
        Session::flash('success', 'Suunto disconnected.');
        redirect('profile/edit');
    }

    public function webhookReceive(array $params): void {
        http_response_code(200);
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];
        if (!empty($data['workoutid'])) {
            PlatformWebhook::queue('suunto', 'create',
                (string)($data['username'] ?? ''),
                (string)$data['workoutid'],
                $raw
            );
        }
        exit;
    }
}

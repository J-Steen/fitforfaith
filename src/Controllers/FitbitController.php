<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\PlatformToken;
use App\Models\PlatformWebhook;
use App\Models\User;
use App\Services\FitbitService;

class FitbitController {
    public function connect(array $params): void {
        Auth::require();
        $state = bin2hex(random_bytes(16));
        Session::set('fitbit_state', $state);
        redirect(FitbitService::getAuthUrl($state));
    }

    public function callback(array $params): void {
        Auth::require();
        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';

        if (!$code || $state !== Session::get('fitbit_state')) {
            Session::flash('error', 'Fitbit authorisation failed. Please try again.');
            redirect('profile/edit');
        }
        Session::forget('fitbit_state');

        $user = Auth::user();
        $tokens = FitbitService::exchangeCode($code);

        PlatformToken::store((int)$user['id'], 'fitbit', $tokens);

        // Set as active platform
        User::update((int)$user['id'], ['fitness_platform' => 'fitbit']);
        Auth::refreshUser();

        // Subscribe to webhook notifications
        try {
            FitbitService::subscribe($tokens['access_token'], (string)$user['id']);
        } catch (\Throwable $e) {
            app_log('Fitbit subscribe error: ' . $e->getMessage(), 'WARN');
        }

        // Queue initial activity backfill
        PlatformWebhook::queue('fitbit', 'backfill', $tokens['platform_user_id'], null, null);

        Session::flash('success', 'Fitbit connected! Your activities will sync shortly.');
        redirect('dashboard');
    }

    public function disconnect(array $params): void {
        Auth::require();
        $user = Auth::user();
        PlatformToken::delete((int)$user['id'], 'fitbit');
        User::update((int)$user['id'], ['fitness_platform' => null]);
        Auth::refreshUser();
        Session::flash('success', 'Fitbit disconnected.');
        redirect('profile/edit');
    }

    /** GET — Fitbit subscriber verification */
    public function webhookVerify(array $params): void {
        $verify = $_GET['verify'] ?? '';
        if ($verify === FITBIT_VERIFY_CODE) {
            http_response_code(204);
        } else {
            http_response_code(404);
        }
        exit;
    }

    /** POST — Fitbit activity notification */
    public function webhookReceive(array $params): void {
        http_response_code(204);
        $raw  = file_get_contents('php://input');
        $events = json_decode($raw, true) ?? [];
        foreach ($events as $evt) {
            if (($evt['collectionType'] ?? '') !== 'activities') continue;
            PlatformWebhook::queue(
                'fitbit',
                $evt['ownerType'] === 'user' ? 'create' : 'update',
                (string)($evt['ownerId'] ?? ''),
                null,
                json_encode($evt)
            );
        }
        exit;
    }
}

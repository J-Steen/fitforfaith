<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\PlatformToken;
use App\Models\PlatformWebhook;
use App\Models\User;
use App\Services\GarminService;

class GarminController {
    /** Step 1: Get request token, store secret, redirect user. */
    public function connect(array $params): void {
        Auth::require();
        if (str_starts_with(GARMIN_CONSUMER_KEY, 'YOUR_')) {
            Session::flash('error', 'Garmin Connect integration is not yet configured. Garmin requires partner approval — please contact support or choose a different platform.');
            redirect('profile/edit');
        }
        $reqToken = GarminService::getRequestToken();
        Session::set('garmin_token_secret', $reqToken['oauth_token_secret']);
        redirect(GarminService::getAuthUrl($reqToken['oauth_token']));
    }

    /** Step 2: User returns from Garmin with oauth_token + oauth_verifier. */
    public function callback(array $params): void {
        Auth::require();
        $oauthToken    = $_GET['oauth_token']    ?? '';
        $oauthVerifier = $_GET['oauth_verifier'] ?? '';
        $tokenSecret   = Session::get('garmin_token_secret', '');

        if (!$oauthToken || !$oauthVerifier) {
            Session::flash('error', 'Garmin authorisation failed. Please try again.');
            redirect('profile/edit');
        }
        Session::forget('garmin_token_secret');

        $user   = Auth::user();
        $tokens = GarminService::exchangeToken($oauthToken, $tokenSecret, $oauthVerifier);

        PlatformToken::store((int)$user['id'], 'garmin', $tokens);
        User::update((int)$user['id'], ['fitness_platform' => 'garmin']);
        Auth::refreshUser();

        PlatformWebhook::queue('garmin', 'backfill', $tokens['platform_user_id'], null, null);

        Session::flash('success', 'Garmin connected! Your activities will sync shortly.');
        redirect('dashboard');
    }

    public function disconnect(array $params): void {
        Auth::require();
        $user = Auth::user();
        PlatformToken::delete((int)$user['id'], 'garmin');
        User::update((int)$user['id'], ['fitness_platform' => null]);
        Auth::refreshUser();
        Session::flash('success', 'Garmin disconnected.');
        redirect('profile/edit');
    }

    /** Garmin pushes activity summaries here. */
    public function webhookReceive(array $params): void {
        http_response_code(200);
        $raw    = file_get_contents('php://input');
        $data   = json_decode($raw, true) ?? [];
        $acts   = $data['activityFiles'] ?? $data['activities'] ?? [];
        foreach ($acts as $act) {
            $uid = (string)($act['userId'] ?? '');
            $eid = (string)($act['activityId'] ?? $act['summaryId'] ?? '');
            if ($uid && $eid) {
                PlatformWebhook::queue('garmin', 'create', $uid, $eid, json_encode($act));
            }
        }
        exit;
    }
}

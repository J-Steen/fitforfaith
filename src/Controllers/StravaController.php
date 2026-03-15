<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\Webhook;
use App\Services\StravaService;

class StravaController {
    public function connect(array $params): void {
        Auth::require();
        $state   = bin2hex(random_bytes(16));
        Session::set('strava_state', $state);
        $authUrl = StravaService::getAuthUrl($state);
        header('Location: ' . $authUrl);
        exit;
    }

    public function callback(array $params): void {
        Auth::require();
        $user = Auth::user();

        // Verify state token
        $state = $_GET['state'] ?? '';
        if (!hash_equals(Session::get('strava_state', ''), $state)) {
            Session::flash('error', 'Invalid Strava authentication state. Please try again.');
            redirect('dashboard');
        }
        Session::forget('strava_state');

        if (isset($_GET['error'])) {
            Session::flash('error', 'Strava connection was cancelled.');
            redirect('dashboard');
        }

        $code = $_GET['code'] ?? '';
        if (!$code) {
            Session::flash('error', 'No authorization code received from Strava.');
            redirect('dashboard');
        }

        try {
            $tokens  = StravaService::exchangeCode($code);
            $athlete = $tokens['athlete'] ?? [];
            $athleteId = (int)($athlete['id'] ?? 0);

            if (!$athleteId) {
                throw new \RuntimeException('Could not retrieve Strava athlete ID.');
            }

            // Check if another user already has this Strava account
            $existing = User::findByStravaId($athleteId);
            if ($existing && (int)$existing['id'] !== (int)$user['id']) {
                Session::flash('error', 'This Strava account is already connected to another user.');
                redirect('dashboard');
            }

            User::linkStrava(
                (int)$user['id'],
                $athleteId,
                $tokens['access_token'],
                $tokens['refresh_token'],
                (int)$tokens['expires_at']
            );

            // Trigger initial activity import in background via webhook queue
            // (or we can fetch here — but for large histories, queue it)
            Session::flash('success', 'Strava connected successfully! Your activities will be imported shortly.');
            redirect('dashboard');

        } catch (\Throwable $e) {
            app_log('Strava callback error: ' . $e->getMessage(), 'ERROR');
            Session::flash('error', 'Strava connection failed. Please try again.');
            redirect('dashboard');
        }
    }

    public function disconnect(array $params): void {
        Auth::require();
        $user = Auth::user();
        User::unlinkStrava((int)$user['id']);
        Session::flash('success', 'Strava account disconnected.');
        redirect('dashboard');
    }

    /**
     * Strava webhook subscription verification (GET).
     */
    public function webhookChallenge(array $params): void {
        $mode      = $_GET['hub_mode']         ?? '';
        $challenge = $_GET['hub_challenge']    ?? '';
        $verToken  = $_GET['hub_verify_token'] ?? '';

        if (StravaService::verifyWebhookChallenge($mode, $challenge, $verToken)) {
            header('Content-Type: application/json');
            echo json_encode(['hub.challenge' => $challenge]);
            exit;
        }
        http_response_code(403);
        exit('Forbidden');
    }

    /**
     * Strava webhook event receiver (POST).
     * Must respond within 2 seconds — just queue and return 200.
     */
    public function webhookReceive(array $params): void {
        $raw     = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!$payload || !isset($payload['object_type'])) {
            http_response_code(400);
            exit;
        }

        // Only process activity events
        if ($payload['object_type'] === 'activity') {
            Webhook::queue($payload);
            app_log("Webhook queued: {$payload['aspect_type']} activity {$payload['object_id']} for athlete {$payload['owner_id']}");
        }

        http_response_code(200);
        echo 'OK';
        exit;
    }

    public function connectPage(array $params): void {
        Auth::require();
        $user = Auth::user();
        $pageTitle = 'Connect Strava — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'strava/connect.php';
        include VIEW_PATH . 'layout/footer.php';
    }
}

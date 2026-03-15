<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\ManualActivity;
use App\Models\User;
use App\Services\PointsService;
use App\Services\LeaderboardCacheService;

class ActivitiesController {
    public function index(array $params): void {
        Auth::require();
        $user       = Auth::user();
        $activities = ManualActivity::getForUser((int)$user['id'], 50);
        $pageTitle  = 'My Activities — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'activities/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function logForm(array $params): void {
        Auth::require();
        $errors    = Session::getFlash('errors') ?? [];
        $old       = Session::getFlash('old')    ?? [];
        $pageTitle = 'Log Activity — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'activities/log.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function store(array $params): void {
        Auth::require();
        $user = Auth::user();

        $type     = $_POST['activity_type'] ?? '';
        $platform = $_POST['platform']      ?? 'other';
        $name     = trim($_POST['name']     ?? '');
        $km       = (float)($_POST['distance_km'] ?? 0);
        $hours    = (int)($_POST['hours']   ?? 0);
        $minutes  = (int)($_POST['minutes'] ?? 0);
        $seconds  = (int)($_POST['seconds'] ?? 0);
        $date     = $_POST['start_date']    ?? '';
        $notes    = trim($_POST['notes']    ?? '');

        $errors = [];
        if (!in_array($type, ['Run', 'Walk', 'Ride'])) $errors[] = 'Please select an activity type.';
        if ($km <= 0)                                   $errors[] = 'Distance must be greater than 0.';
        if (!$date || !strtotime($date))                $errors[] = 'Please enter a valid date.';
        if (strtotime($date) > time())                  $errors[] = 'Activity date cannot be in the future.';
        if (!array_key_exists($platform, ManualActivity::PLATFORMS)) $platform = 'other';

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            redirect('activities/log');
        }

        $distanceMeters = $km * 1000;
        $movingTimeSec  = $hours * 3600 + $minutes * 60 + $seconds;

        ManualActivity::create([
            'user_id'         => $user['id'],
            'church_id'       => $user['church_id'],
            'activity_type'   => $type,
            'platform'        => $platform,
            'name'            => $name ?: null,
            'distance_meters' => $distanceMeters,
            'moving_time_sec' => $movingTimeSec,
            'start_date'      => $date,
            'notes'           => $notes ?: null,
        ]);

        Session::flash('success', 'Activity submitted! It will earn points once an admin approves it.');
        redirect('activities');
    }
}

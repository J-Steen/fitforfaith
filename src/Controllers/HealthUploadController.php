<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\PlatformActivity;
use App\Models\User;
use App\Services\PointsService;
use App\Services\LeaderboardCacheService;

class HealthUploadController {

    public function showUpload(array $params): void {
        Auth::require();
        $user      = Auth::user();
        $pageTitle = 'Upload Health Data — ' . APP_NAME;
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'platforms/upload.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function upload(array $params): void {
        Auth::require();
        $user     = Auth::user();
        $platform = $user['fitness_platform'] ?? '';

        if (!in_array($platform, ['apple_health', 'samsung'])) {
            Session::flash('error', 'File upload is only available for Apple Health and Samsung Health.');
            redirect('profile/edit');
        }

        if (empty($_FILES['health_file']['tmp_name'])) {
            Session::flash('error', 'No file selected. Please choose a file to upload.');
            redirect('health/upload');
        }

        $file = $_FILES['health_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        try {
            $activities = match ($platform) {
                'apple_health' => $this->parseAppleHealth($file['tmp_name']),
                'samsung'      => $this->parseSamsungHealth($file['tmp_name'], $ext),
                default        => [],
            };

            $count = 0;
            foreach ($activities as $act) {
                $points = PointsService::calculateFinal(
                    (int)$user['id'],
                    $act['activity_type'],
                    $act['distance_meters'],
                    $act['start_date']
                );

                PlatformActivity::upsert([
                    'user_id'         => (int)$user['id'],
                    'church_id'       => $user['church_id'] ?? null,
                    'platform'        => $platform,
                    'external_id'     => $act['external_id'],
                    'activity_type'   => $act['activity_type'],
                    'name'            => $act['name'],
                    'distance_meters' => $act['distance_meters'],
                    'moving_time_sec' => $act['moving_time_sec'],
                    'start_date'      => $act['start_date'],
                    'points_awarded'  => $points,
                    'raw_payload'     => $act['raw'] ?? null,
                ]);
                $count++;
            }

            LeaderboardCacheService::rebuildUser((int)$user['id']);

            Session::flash('success', "Import successful — {$count} eligible activities imported.");
        } catch (\Throwable $e) {
            app_log('Health upload error: ' . $e->getMessage(), 'ERROR');
            Session::flash('error', 'Could not parse the file. Please make sure it is a valid export.');
        }

        redirect('health/upload');
    }

    // ── Apple Health (export.xml) ─────────────────────────────────

    private function parseAppleHealth(string $filePath): array {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($filePath);
        if (!$xml) throw new \RuntimeException('Invalid Apple Health XML file.');

        $activities = [];
        foreach ($xml->Workout as $w) {
            $type = $this->normalizeAppleType((string)($w['workoutActivityType'] ?? ''));
            if (!$type) continue;

            $distM   = 0.0;
            $durSec  = (int)((float)($w['duration'] ?? 0));
            $durUnit = strtolower((string)($w['durationUnit'] ?? 'min'));
            if ($durUnit === 'min') $durSec = (int)($durSec * 60);

            foreach ($w->WorkoutStatistics as $stat) {
                if ((string)$stat['type'] === 'HKQuantityTypeIdentifierDistanceWalkingRunning'
                    || (string)$stat['type'] === 'HKQuantityTypeIdentifierDistanceCycling') {
                    $val  = (float)($stat['sum'] ?? 0);
                    $unit = strtolower((string)($stat['unit'] ?? 'km'));
                    $distM = $unit === 'm' ? $val : $val * 1000;
                }
            }

            $startRaw = (string)($w['startDate'] ?? '');
            $startDt  = $startRaw ? date('Y-m-d H:i:s', strtotime($startRaw)) : date('Y-m-d H:i:s');
            $extId    = 'apple_' . md5($startRaw . $type . $distM);

            $activities[] = [
                'external_id'     => $extId,
                'activity_type'   => $type,
                'name'            => $type,
                'distance_meters' => $distM,
                'moving_time_sec' => $durSec,
                'start_date'      => $startDt,
                'raw'             => json_encode((array)$w->attributes()),
            ];
        }
        return $activities;
    }

    private function normalizeAppleType(string $raw): ?string {
        $raw = strtolower($raw);
        if (str_contains($raw, 'running') || str_contains($raw, 'jogging')) return 'Run';
        if (str_contains($raw, 'walking') || str_contains($raw, 'hiking'))  return 'Walk';
        if (str_contains($raw, 'cycling') || str_contains($raw, 'biking'))  return 'Ride';
        return null;
    }

    // ── Samsung Health (CSV) ──────────────────────────────────────

    private function parseSamsungHealth(string $filePath, string $ext): array {
        if ($ext !== 'csv') throw new \RuntimeException('Samsung Health expects a CSV file.');

        $handle = fopen($filePath, 'r');
        if (!$handle) throw new \RuntimeException('Cannot open file.');

        // Skip comment lines starting with #
        $header = null;
        $activities = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || str_starts_with(trim($row[0] ?? ''), '#')) continue;

            if ($header === null) {
                $header = array_map('trim', $row);
                continue;
            }

            $data = array_combine($header, $row);

            $type = $this->normalizeSamsungType($data['exercise_type'] ?? $data['type'] ?? '');
            if (!$type) continue;

            $distM  = (float)($data['distance'] ?? 0);  // Samsung exports in metres
            $durSec = (int)($data['duration'] ?? 0);     // Samsung exports in seconds
            $start  = $data['start_time'] ?? $data['date_time'] ?? date('Y-m-d H:i:s');
            $startDt = date('Y-m-d H:i:s', strtotime($start));
            $extId   = 'samsung_' . md5($start . $type . $distM);

            $activities[] = [
                'external_id'     => $extId,
                'activity_type'   => $type,
                'name'            => ucfirst(strtolower($type)),
                'distance_meters' => $distM,
                'moving_time_sec' => $durSec,
                'start_date'      => $startDt,
                'raw'             => json_encode($data),
            ];
        }
        fclose($handle);
        return $activities;
    }

    private function normalizeSamsungType(string $raw): ?string {
        $raw = strtolower(trim($raw));
        // Samsung uses numeric codes or text like "1001" (running), "1002" (jogging), "13" (cycling)
        if (in_array($raw, ['1001','1002','running','jogging','trail running'])) return 'Run';
        if (in_array($raw, ['1003','1004','walking','hiking','1007']))           return 'Walk';
        if (in_array($raw, ['13','cycling','biking','mountain biking']))         return 'Ride';
        if (str_contains($raw, 'run'))  return 'Run';
        if (str_contains($raw, 'walk') || str_contains($raw, 'hike')) return 'Walk';
        if (str_contains($raw, 'cycl') || str_contains($raw, 'bik'))  return 'Ride';
        return null;
    }
}

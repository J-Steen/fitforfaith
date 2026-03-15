<?php /** @var array $activities */ ?>

<div class="container" style="padding:32px 20px;">
  <div class="flex-between mb-4">
    <div>
      <h1 class="section-title">My Manual Activities</h1>
      <p class="text-muted">Activities you've submitted for review. Strava activities sync automatically.</p>
    </div>
    <a href="<?= url('activities/log') ?>" class="btn btn-primary">+ Log Activity</a>
  </div>

  <?php $success = \App\Core\Session::getFlash('success'); if ($success): ?>
    <div class="alert alert-success mb-4"><?= h($success) ?></div>
  <?php endif; ?>

  <?php if (empty($activities)): ?>
    <div class="card text-center" style="padding:48px;">
      <div style="font-size:3rem; margin-bottom:12px;"><i class="fa-solid fa-file-pen"></i></div>
      <div class="fw-bold mb-2">No manual activities yet</div>
      <p class="text-muted text-sm mb-4">Log a run, walk, or ride from any fitness platform — Garmin, Fitbit, Apple Watch, and more.</p>
      <a href="<?= url('activities/log') ?>" class="btn btn-primary">Log Your First Activity</a>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Name</th>
              <th>Platform</th>
              <th>Distance</th>
              <th>Duration</th>
              <th>Status</th>
              <th>Points</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activities as $a): ?>
              <?php
                $statusBadge = match($a['status']) {
                  'approved' => '<span class="badge badge-green"><i class="fa-solid fa-check"></i> Approved</span>',
                  'rejected' => '<span class="badge badge-red"><i class="fa-solid fa-xmark"></i> Rejected</span>',
                  default    => '<span class="badge badge-yellow"><i class="fa-solid fa-hourglass-half"></i> Pending</span>',
                };
                $platformLabel = \App\Models\ManualActivity::PLATFORMS[$a['platform']] ?? ucfirst($a['platform']);
              ?>
              <tr>
                <td class="text-muted text-sm"><?= date('j M Y', strtotime($a['start_date'])) ?></td>
                <td><?= activity_icon($a['activity_type']) ?> <?= h($a['activity_type']) ?></td>
                <td class="text-sm"><?= h($a['name'] ?: '—') ?></td>
                <td class="text-sm text-muted"><?= h($platformLabel) ?></td>
                <td class="text-sm"><?= fmt_km($a['distance_meters']) ?></td>
                <td class="text-sm"><?= $a['moving_time_sec'] ? fmt_duration($a['moving_time_sec']) : '—' ?></td>
                <td>
                  <?= $statusBadge ?>
                  <?php if ($a['status'] === 'rejected' && $a['reject_reason']): ?>
                    <div class="text-xs text-muted mt-1"><?= h($a['reject_reason']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="fw-bold gradient-text"><?= $a['status'] === 'approved' ? fmt_points((int)$a['points_awarded']) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php /** @var array $user @var array $activities @var array $totals */ ?>

<div class="admin-page-header">
  <div class="admin-page-title"><?= h($user['first_name'] . ' ' . $user['last_name']) ?></div>
  <div style="display:flex;gap:8px;">
    <a href="<?= url('admin/users/' . $user['id'] . '/edit') ?>" class="btn btn-primary btn-sm">Edit User</a>
    <a href="<?= url('admin/users') ?>" class="btn btn-secondary btn-sm">← Back</a>
  </div>
</div>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
  <div class="card" style="padding:16px;">
    <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase;letter-spacing:.05em;">Total Points</div>
    <div class="fw-bold gradient-text" style="font-size:1.5rem;"><?= number_format((int)$totals['total_points']) ?></div>
  </div>
  <div class="card" style="padding:16px;">
    <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase;letter-spacing:.05em;">Activities</div>
    <div class="fw-bold" style="font-size:1.5rem;"><?= number_format((int)$totals['activity_count']) ?></div>
  </div>
  <div class="card" style="padding:16px;">
    <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase;letter-spacing:.05em;">Church</div>
    <div class="fw-bold"><?= h($user['church_name'] ?? '—') ?></div>
  </div>
  <div class="card" style="padding:16px;">
    <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase;letter-spacing:.05em;">Email</div>
    <div class="text-sm" style="word-break:break-all;"><?= h($user['email']) ?></div>
  </div>
  <div class="card" style="padding:16px;">
    <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase;letter-spacing:.05em;">Status</div>
    <?= $user['is_paid'] ? '<span class="badge badge-green">Paid</span>' : '<span class="badge badge-yellow">Pending</span>' ?>
    <?= $user['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Disabled</span>' ?>
  </div>
  <div class="card" style="padding:16px;">
    <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase;letter-spacing:.05em;">Strava</div>
    <?php if ($user['strava_athlete_id']): ?>
      <span class="badge badge-blue"><i class="fa-solid fa-bolt"></i> Connected</span>
    <?php else: ?>
      <span class="badge badge-gray">Not linked</span>
    <?php endif; ?>
  </div>
</div>

<!-- Activities table -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h3 class="fw-bold" style="font-size:1rem;margin:0;">Activities</h3>
    <span class="text-muted text-sm"><?= number_format($activities['total']) ?> total</span>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Activity</th>
          <th>Name</th>
          <th>Distance</th>
          <th>Time</th>
          <th>Points</th>
          <th>Flag</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($activities['data'] as $a): ?>
          <tr <?= $a['is_flagged'] ? 'style="opacity:.5;"' : '' ?>>
            <td class="text-sm text-muted"><?= date('j M Y', strtotime($a['start_date'])) ?></td>
            <td>
              <?php
              $typeIcons = ['Run' => 'fa-person-running', 'Walk' => 'fa-person-walking', 'Ride' => 'fa-person-biking', 'VirtualRide' => 'fa-person-biking', 'Hike' => 'fa-person-hiking'];
              $icon = $typeIcons[$a['activity_type']] ?? 'fa-dumbbell';
              ?>
              <i class="fa-solid <?= $icon ?>"></i> <?= h($a['activity_type']) ?>
            </td>
            <td class="text-sm"><?= h($a['name'] ?? '—') ?></td>
            <td><?= number_format(($a['distance_meters'] ?? 0) / 1000, 2) ?> km</td>
            <td class="text-sm text-muted">
              <?php
              $sec = (int)($a['moving_time_sec'] ?? 0);
              echo floor($sec / 3600) ? floor($sec / 3600) . 'h ' : '';
              echo floor(($sec % 3600) / 60) . 'm';
              ?>
            </td>
            <td class="fw-bold gradient-text"><?= number_format((int)$a['points_awarded']) ?></td>
            <td>
              <?php if ($a['is_flagged']): ?>
                <form method="POST" action="<?= url('admin/activities/' . $a['id'] . '/unflag') ?>" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_redirect" value="<?= url('admin/users/' . $user['id']) ?>">
                  <button class="btn btn-success btn-sm">Unflag</button>
                </form>
              <?php else: ?>
                <form method="POST" action="<?= url('admin/activities/' . $a['id'] . '/flag') ?>" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_redirect" value="<?= url('admin/users/' . $user['id']) ?>">
                  <button class="btn btn-danger btn-sm">Flag</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($activities['data'])): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:40px;">No activities yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($activities['pages'] > 1): ?>
<div class="pagination mt-3">
  <?php for ($p = 1; $p <= $activities['pages']; $p++): ?>
    <a href="?page=<?= $p ?>" class="page-btn <?= $p == $activities['page'] ? 'active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php /** @var array $result @var array $filters */ ?>

<div class="admin-page-header">
  <div class="admin-page-title">Activities (<?= number_format($result['total']) ?>)</div>
</div>

<!-- Tab bar -->
<div style="display:flex; gap:4px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,.08); padding-bottom:0;">
  <div style="padding:8px 20px; font-size:.875rem; font-weight:600; color:var(--accent-purple);
              border-bottom:2px solid var(--accent-purple); margin-bottom:-1px;">
    <i class="fa-solid fa-bolt"></i> Strava Activities
  </div>
</div>

<form method="GET" action="<?= url('admin/activities') ?>" class="filter-bar">
  <input type="text" class="form-control" name="user_id" placeholder="User ID"
         value="<?= h($filters['user_id']) ?>" style="max-width:120px;">
  <select class="form-control form-select" name="type" style="max-width:160px;">
    <option value="">All Types</option>
    <option value="Run"  <?= $filters['type']==='Run'  ? 'selected' : '' ?>>Running</option>
    <option value="Walk" <?= $filters['type']==='Walk' ? 'selected' : '' ?>>Walking</option>
    <option value="Ride" <?= $filters['type']==='Ride' ? 'selected' : '' ?>>Cycling</option>
  </select>
  <label style="display:flex; align-items:center; gap:6px; color:var(--text-muted); font-size:.875rem;">
    <input type="checkbox" name="flagged" <?= $filters['flagged'] ? 'checked' : '' ?>>
    Flagged only
  </label>
  <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
  <a href="<?= url('admin/activities') ?>" class="btn btn-secondary btn-sm">Reset</a>
</form>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>User</th>
          <th>Church</th>
          <th>Type</th>
          <th>Name</th>
          <th>Distance</th>
          <th>Duration</th>
          <th>Points</th>
          <th>Date</th>
          <th>Flag</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['data'] as $a): ?>
          <tr <?= $a['is_flagged'] ? 'style="opacity:.5;"' : '' ?>>
            <td class="fw-bold text-sm"><?= h($a['first_name'] . ' ' . $a['last_name']) ?></td>
            <td class="text-muted text-sm"><?= h($a['church_name'] ?? '—') ?></td>
            <td><?= activity_icon($a['activity_type']) ?> <?= h($a['activity_type']) ?></td>
            <td class="text-sm"><?= h(truncate($a['name'] ?? '—', 30)) ?></td>
            <td class="text-sm"><?= fmt_km($a['distance_meters']) ?></td>
            <td class="text-sm"><?= fmt_duration($a['moving_time_sec']) ?></td>
            <td class="fw-bold gradient-text"><?= (int)$a['points_awarded'] ?></td>
            <td class="text-muted text-sm"><?= date('j M Y', strtotime($a['start_date'])) ?></td>
            <td>
              <?php if ($a['is_flagged']): ?>
                <form method="POST" action="<?= url('admin/activities/' . $a['id'] . '/unflag') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-success btn-sm">Unflag</button>
                </form>
              <?php else: ?>
                <form method="POST" action="<?= url('admin/activities/' . $a['id'] . '/flag') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-danger btn-sm">Flag</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($result['data'])): ?>
          <tr><td colspan="9" class="text-center text-muted" style="padding:40px;">No activities found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($result['pages'] > 1): ?>
<div class="pagination">
  <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
    <a href="?page=<?= $p ?>&user_id=<?= urlencode($filters['user_id']) ?>&type=<?= urlencode($filters['type']) ?>"
       class="page-btn <?= $p == $result['page'] ? 'active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

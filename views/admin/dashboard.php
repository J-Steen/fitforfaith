<?php /** @var array $stats @var int $totalRaised @var array $recentUsers @var array $topChurches @var array $topUsers */ ?>

<div class="admin-page-header">
  <div class="admin-page-title">Dashboard</div>
  <div class="text-muted text-sm">Last updated: <?= date('j M Y, H:i') ?></div>
</div>

<!-- Stats -->
<div class="admin-stats">
  <div class="admin-stat">
    <div class="admin-stat-label">Total Participants</div>
    <div class="admin-stat-value gradient-text"><?= number_format($stats['users']) ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-label">Paid Registrations</div>
    <div class="admin-stat-value gradient-text"><?= number_format($stats['paid']) ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-label">Total Points Earned</div>
    <div class="admin-stat-value gradient-text"><?= number_format($stats['points']) ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-label">Activities Logged</div>
    <div class="admin-stat-value gradient-text"><?= number_format($stats['activities']) ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-label">Total Raised</div>
    <div class="admin-stat-value gradient-text"><?= fmt_money($totalRaised) ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-label">Active Churches</div>
    <div class="admin-stat-value gradient-text"><?= number_format($stats['churches']) ?></div>
  </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:8px;">
  <!-- Top churches -->
  <div class="card">
    <div class="flex-between mb-3">
      <h3 class="fw-bold">Top Churches</h3>
      <a href="<?= url('admin/churches') ?>" class="btn btn-secondary btn-sm">Manage →</a>
    </div>
    <?php foreach ($topChurches as $i => $c): ?>
      <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--glass-border);">
        <div>
          <span class="text-muted text-xs me-2"><?= $i+1 ?>.</span>
          <span class="fw-bold text-sm"><?= h($c['name']) ?></span>
          <span class="text-muted text-xs ml-2"><?= $c['member_count'] ?> members</span>
        </div>
        <span class="gradient-text fw-bold"><?= fmt_points((int)$c['total_points']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Top individuals -->
  <div class="card">
    <div class="flex-between mb-3">
      <h3 class="fw-bold">Top Individuals</h3>
      <a href="<?= url('leaderboard') ?>" class="btn btn-secondary btn-sm">Leaderboard →</a>
    </div>
    <?php foreach ($topUsers as $i => $u): ?>
      <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--glass-border);">
        <div>
          <span class="text-muted text-xs me-2"><?= $i+1 ?>.</span>
          <span class="fw-bold text-sm"><?= h($u['first_name'] . ' ' . substr($u['last_name'],0,1)) ?>.</span>
          <?php if ($u['church_name']): ?>
            <span class="text-muted text-xs ml-1"><?= h($u['church_name']) ?></span>
          <?php endif; ?>
        </div>
        <span class="gradient-text fw-bold"><?= fmt_points((int)$u['total_points']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Recent users -->
<div class="card mt-4">
  <div class="flex-between mb-3">
    <h3 class="fw-bold">Recent Registrations</h3>
    <a href="<?= url('admin/users') ?>" class="btn btn-secondary btn-sm">All Users →</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Church</th>
          <th>Paid</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
          <tr>
            <td class="fw-bold"><?= h($u['first_name'] . ' ' . $u['last_name']) ?></td>
            <td class="text-muted text-sm"><?= h($u['email']) ?></td>
            <td class="text-sm"><?= h($u['church_name'] ?? '—') ?></td>
            <td><?= $u['is_paid']
              ? '<span class="badge badge-green">Paid</span>'
              : '<span class="badge badge-yellow">Pending</span>' ?></td>
            <td class="text-muted text-sm"><?= date('j M', strtotime($u['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

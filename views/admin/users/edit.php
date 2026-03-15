<?php /** @var array $user @var array $churches @var array $errors @var array $stravaStats */ ?>

<div class="admin-page-header">
  <div class="admin-page-title">Edit User — <?= h($user['first_name'] . ' ' . $user['last_name']) ?></div>
  <a href="<?= url('admin/users') ?>" class="btn btn-secondary btn-sm">← Back</a>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
  <div class="card">
    <form method="POST" action="<?= url('admin/users/' . $user['id'] . '/update') ?>">
      <?= csrf_field() ?>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label">First Name</label>
          <input type="text" class="form-control" name="first_name" value="<?= h($user['first_name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Last Name</label>
          <input type="text" class="form-control" name="last_name" value="<?= h($user['last_name']) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled>
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="tel" class="form-control" name="phone" value="<?= h($user['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Church</label>
        <select class="form-control form-select" name="church_id">
          <option value="">— None —</option>
          <?php foreach ($churches as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $user['church_id'] == $c['id'] ? 'selected' : '' ?>>
              <?= h($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <input type="hidden" name="fitness_platform" value="strava">
      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label">Payment Status</label>
          <select class="form-control form-select" name="is_paid">
            <option value="1" <?= $user['is_paid'] ? 'selected' : '' ?>>Paid</option>
            <option value="0" <?= !$user['is_paid'] ? 'selected' : '' ?>>Pending</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Account Status</label>
          <select class="form-control form-select" name="is_active">
            <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Disabled</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-control form-select" name="role">
            <option value="user"  <?= $user['role'] === 'user'  ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>

  <div>
    <div class="card mb-3">
      <div class="text-xs text-muted fw-bold mb-2" style="text-transform:uppercase; letter-spacing:.05em;">Status</div>
      <div class="mb-2"><?= $user['is_active']
        ? '<span class="badge badge-green">Active</span>'
        : '<span class="badge badge-red">Inactive</span>' ?></div>
      <div class="mb-2"><?= $user['strava_athlete_id']
        ? '<span class="badge badge-blue">Strava Connected</span>'
        : '<span class="badge badge-gray">No Strava</span>' ?></div>
      <div class="text-muted text-xs mt-2">Joined: <?= date('j M Y', strtotime($user['created_at'])) ?></div>
      <?php if ($user['strava_connected_at']): ?>
        <div class="text-muted text-xs">Strava: <?= date('j M Y', strtotime($user['strava_connected_at'])) ?></div>
      <?php endif; ?>
    </div>
    <form method="POST" action="<?= url('admin/users/' . $user['id'] . '/delete') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger btn-sm btn-block"
              onclick="return confirm('Delete this user? This cannot be undone.')">
        Delete User
      </button>
    </form>
  </div>
</div>

<!-- Strava Support Panel -->
<div class="card mt-4">
  <h3 class="fw-bold mb-4" style="font-size:1rem;">
    <i class="fa-solid fa-bolt" style="color:#FC4C02;"></i> Strava Support
  </h3>

  <?php if ($user['strava_athlete_id']): ?>
    <?php
      $tokenExpires  = $user['strava_token_expires'] ? (int)$user['strava_token_expires'] : null;
      $tokenExpired  = $tokenExpires && $tokenExpires < time();
      $tokenExpiresStr = $tokenExpires ? date('j M Y H:i', $tokenExpires) : '—';
    ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:16px; margin-bottom:20px;">
      <div>
        <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase; letter-spacing:.05em;">Athlete ID</div>
        <div class="fw-bold"><?= h($user['strava_athlete_id']) ?></div>
        <a href="https://www.strava.com/athletes/<?= h($user['strava_athlete_id']) ?>"
           target="_blank" rel="noopener"
           class="text-xs" style="color:var(--accent-purple);">
          View on Strava <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>
      </div>
      <div>
        <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase; letter-spacing:.05em;">Connected</div>
        <div class="fw-bold"><?= $user['strava_connected_at'] ? date('j M Y', strtotime($user['strava_connected_at'])) : '—' ?></div>
      </div>
      <div>
        <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase; letter-spacing:.05em;">Token Expires</div>
        <div class="fw-bold <?= $tokenExpired ? 'text-danger' : '' ?>"><?= $tokenExpiresStr ?></div>
        <?php if ($tokenExpired): ?>
          <span class="badge badge-red" style="font-size:.7rem;">Expired — will refresh on next sync</span>
        <?php else: ?>
          <span class="badge badge-green" style="font-size:.7rem;">Valid</span>
        <?php endif; ?>
      </div>
      <div>
        <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase; letter-spacing:.05em;">Activities Synced</div>
        <div class="fw-bold"><?= number_format($stravaStats['count']) ?></div>
        <?php if ($stravaStats['last_date']): ?>
          <div class="text-xs text-muted">Last: <?= date('j M Y', strtotime($stravaStats['last_date'])) ?></div>
        <?php endif; ?>
      </div>
      <div>
        <div class="text-xs text-muted fw-bold mb-1" style="text-transform:uppercase; letter-spacing:.05em;">Total Points</div>
        <div class="fw-bold gradient-text"><?= number_format($stravaStats['total_points']) ?></div>
      </div>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; border-top:1px solid rgba(255,255,255,.08); padding-top:16px;">
      <form method="POST" action="<?= url('admin/users/' . $user['id'] . '/strava-disconnect') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger btn-sm"
                onclick="return confirm('Disconnect Strava for this user? Their activity history will be kept.')">
          <i class="fa-solid fa-link-slash"></i> Disconnect Strava
        </button>
      </form>
      <span class="text-xs text-muted">Disconnecting clears their OAuth tokens. The user will need to reconnect.</span>
    </div>

  <?php else: ?>
    <div style="display:flex; align-items:center; gap:16px; padding:12px 0;">
      <span class="badge badge-gray" style="font-size:.875rem;"><i class="fa-solid fa-circle-xmark"></i> Not connected</span>
      <span class="text-muted text-sm">This user has not linked a Strava account.</span>
    </div>
  <?php endif; ?>
</div>

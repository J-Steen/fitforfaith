<?php /** @var array $settingsMap */ ?>

<div class="admin-page-header">
  <div class="admin-page-title">Platform Settings</div>
</div>

<form method="POST" action="<?= url('admin/settings') ?>">
  <?= csrf_field() ?>
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

    <!-- Points rules -->
    <div class="card">
      <h3 class="fw-bold mb-4">Points Rules</h3>
      <div class="form-group">
        <label class="form-label">Points per km — Running <i class="fa-solid fa-person-running"></i></label>
        <input type="number" class="form-control" name="points_per_km_run" min="0" max="100"
               value="<?= h($settingsMap['points_per_km_run'] ?? 10) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Points per km — Walking <i class="fa-solid fa-person-walking"></i></label>
        <input type="number" class="form-control" name="points_per_km_walk" min="0" max="100"
               value="<?= h($settingsMap['points_per_km_walk'] ?? 5) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Points per km — Cycling <i class="fa-solid fa-person-biking"></i></label>
        <input type="number" class="form-control" name="points_per_km_ride" min="0" max="100"
               value="<?= h($settingsMap['points_per_km_ride'] ?? 3) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Max Points Per Day (per user)</label>
        <input type="number" class="form-control" name="max_points_per_day" min="0" max="10000"
               value="<?= h($settingsMap['max_points_per_day'] ?? 200) ?>">
        <span class="form-hint">Set to 0 for no limit.</span>
      </div>
    </div>

    <!-- Event settings -->
    <div class="card">
      <h3 class="fw-bold mb-4">Event Settings</h3>
      <div class="form-group">
        <label class="form-label">Event Start Date</label>
        <input type="date" class="form-control" name="event_start_date"
               value="<?= h($settingsMap['event_start_date'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Event End Date</label>
        <input type="date" class="form-control" name="event_end_date"
               value="<?= h($settingsMap['event_end_date'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Registration Fee (ZAR cents)</label>
        <input type="number" class="form-control" name="registration_fee" min="0"
               value="<?= h($settingsMap['registration_fee'] ?? 15000) ?>">
        <span class="form-hint">e.g. 15000 = R150.00</span>
      </div>
      <div class="form-group">
        <label class="form-check">
          <input type="checkbox" class="form-check-input" name="registration_open" value="1"
                 <?= ($settingsMap['registration_open'] ?? '1') === '1' ? 'checked' : '' ?>>
          <span class="form-check-label fw-bold">Registration Open</span>
        </label>
        <span class="form-hint">Uncheck to disable new registrations.</span>
      </div>
    </div>

    <!-- Site branding -->
    <div class="card">
      <h3 class="fw-bold mb-4">Site Branding</h3>
      <div class="form-group">
        <label class="form-label">Site Name</label>
        <input type="text" class="form-control" name="site_name"
               value="<?= h($settingsMap['site_name'] ?? APP_NAME) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Tagline</label>
        <input type="text" class="form-control" name="site_tagline"
               value="<?= h($settingsMap['site_tagline'] ?? APP_TAGLINE) ?>">
      </div>
    </div>

  </div>

  <div class="mt-4">
    <button type="submit" class="btn btn-primary btn-lg">Save All Settings</button>
    <p class="text-muted text-sm mt-2">Settings are cached for up to 10 minutes after saving.</p>
  </div>
</form>

<!-- Strava Webhook -->
<div class="card mt-4" style="max-width:480px;">
  <h3 class="fw-bold mb-2"><i class="fa-solid fa-bolt" style="color:#FC4C02;"></i> Strava Webhook</h3>
  <p class="text-muted text-sm mb-4">Register this app with Strava so new activities sync automatically. Only needs to be done once.</p>
  <form method="POST" action="<?= url('admin/strava-register-webhook') ?>">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-primary">Register Webhook with Strava</button>
  </form>
</div>

<!-- Change Password -->
<div class="card mt-4" style="max-width:480px;">
  <h3 class="fw-bold mb-4"><i class="fa-solid fa-lock" style="color:var(--accent-purple);"></i> Change Admin Password</h3>
  <form method="POST" action="<?= url('admin/change-password') ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label class="form-label">Current Password</label>
      <input type="password" class="form-control" name="current_password" required autocomplete="current-password">
    </div>
    <div class="form-group">
      <label class="form-label">New Password</label>
      <input type="password" class="form-control" name="new_password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="form-group">
      <label class="form-label">Confirm New Password</label>
      <input type="password" class="form-control" name="confirm_password" required minlength="8" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary">Update Password</button>
  </form>
</div>

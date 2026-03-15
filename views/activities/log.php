<?php
/** @var array $errors @var array $old */
$old = $old ?? [];
?>
<div class="container" style="padding:32px 20px; max-width:680px;">
  <div class="mb-4">
    <a href="<?= url('activities') ?>" class="text-muted text-sm">← My Activities</a>
    <h1 class="section-title mt-2">Log an Activity</h1>
    <p class="text-muted">Don't have Strava? No problem — log any run, walk, or ride manually. An admin will review and approve it.</p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
      <?php foreach ($errors as $e): ?><div><i class="fa-solid fa-triangle-exclamation"></i> <?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" action="<?= url('activities/log') ?>">
      <?= csrf_field() ?>

      <!-- Activity Type -->
      <div class="form-group">
        <label class="form-label">Activity Type <span style="color:var(--accent-red);">*</span></label>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <?php foreach (['Run' => '<i class="fa-solid fa-person-running"></i> Running', 'Walk' => '<i class="fa-solid fa-person-walking"></i> Walking', 'Ride' => '<i class="fa-solid fa-person-biking"></i> Cycling'] as $val => $label): ?>
            <label style="cursor:pointer; display:flex; align-items:center; gap:8px;
                          padding:10px 18px; border-radius:8px; border:1px solid rgba(255,255,255,.12);
                          background:<?= ($old['activity_type'] ?? '') === $val ? 'rgba(139,92,246,.25)' : 'rgba(255,255,255,.04)' ?>;">
              <input type="radio" name="activity_type" value="<?= $val ?>"
                     <?= ($old['activity_type'] ?? '') === $val ? 'checked' : '' ?> style="accent-color:var(--accent-purple);">
              <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Platform -->
      <div class="form-group">
        <label class="form-label">Platform / Tracking App <span style="color:var(--accent-red);">*</span></label>
        <select class="form-control form-select" name="platform">
          <?php foreach (\App\Models\ManualActivity::PLATFORMS as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($old['platform'] ?? 'other') === $key ? 'selected' : '' ?>>
              <?= h($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="text-xs text-muted mt-1">Which app or device did you use to track this activity?</div>
      </div>

      <!-- Activity Name -->
      <div class="form-group">
        <label class="form-label">Activity Name <span class="text-muted">(optional)</span></label>
        <input type="text" class="form-control" name="name" placeholder="e.g. Morning Run, Evening Walk…"
               value="<?= h($old['name'] ?? '') ?>">
      </div>

      <!-- Distance -->
      <div class="form-group">
        <label class="form-label">Distance <span style="color:var(--accent-red);">*</span></label>
        <div style="display:flex; align-items:center; gap:10px;">
          <input type="number" class="form-control" name="distance_km" placeholder="0.00" step="0.01" min="0.01"
                 value="<?= h($old['distance_km'] ?? '') ?>" style="max-width:160px;" required>
          <span class="text-muted">km</span>
        </div>
      </div>

      <!-- Duration -->
      <div class="form-group">
        <label class="form-label">Duration <span class="text-muted">(optional)</span></label>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          <input type="number" class="form-control" name="hours"   placeholder="0" min="0" max="24"
                 value="<?= (int)($old['hours']   ?? 0) ?>" style="max-width:80px;"> <span class="text-muted text-sm">h</span>
          <input type="number" class="form-control" name="minutes" placeholder="0" min="0" max="59"
                 value="<?= (int)($old['minutes'] ?? 0) ?>" style="max-width:80px;"> <span class="text-muted text-sm">min</span>
          <input type="number" class="form-control" name="seconds" placeholder="0" min="0" max="59"
                 value="<?= (int)($old['seconds'] ?? 0) ?>" style="max-width:80px;"> <span class="text-muted text-sm">sec</span>
        </div>
      </div>

      <!-- Date -->
      <div class="form-group">
        <label class="form-label">Activity Date <span style="color:var(--accent-red);">*</span></label>
        <input type="date" class="form-control" name="start_date"
               value="<?= h($old['start_date'] ?? date('Y-m-d')) ?>"
               max="<?= date('Y-m-d') ?>" style="max-width:200px;" required>
      </div>

      <!-- Notes -->
      <div class="form-group">
        <label class="form-label">Notes <span class="text-muted">(optional)</span></label>
        <textarea class="form-control" name="notes" rows="3"
                  placeholder="Any details you'd like to share with the admin for verification…"><?= h($old['notes'] ?? '') ?></textarea>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Submit Activity</button>
        <a href="<?= url('activities') ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <div class="card mt-4" style="border-color:rgba(139,92,246,.3); background:rgba(139,92,246,.06);">
    <div class="fw-bold mb-2"><i class="fa-solid fa-circle-info"></i> How manual activities work</div>
    <ul style="margin:0; padding-left:1.2rem; color:var(--text-muted); font-size:.875rem; line-height:1.7;">
      <li>Submit your activity with distance, date, and platform used.</li>
      <li>An admin will review and approve it — usually within 24 hours.</li>
      <li>Points are calculated and added to your total once approved.</li>
      <li>You can also <a href="<?= url('strava/connect-page') ?>" style="color:var(--accent-purple);">connect Strava</a> for fully automatic syncing.</li>
    </ul>
  </div>
</div>

<?php /** @var array $user */ ?>
<?php
  $platform = $user['fitness_platform'] ?? '';
  $isApple  = $platform === 'apple_health';
  $isSamsung = $platform === 'samsung';
?>
<div class="container container-sm" style="padding: 32px 20px;">
  <h1 class="section-title mb-4">Upload Health Data</h1>

  <?php if (!$isApple && !$isSamsung): ?>
    <div class="card">
      <p class="text-muted">File upload is only available if your selected platform is <strong>Apple Health</strong> or <strong>Samsung Health</strong>.</p>
      <a href="<?= url('profile/edit') ?>" class="btn btn-secondary mt-3">Back to Profile</a>
    </div>
  <?php else: ?>

  <div class="card mb-4">
    <?php if ($isApple): ?>
      <h2 class="fw-bold mb-1" style="font-size:1.05rem;">🍎 Apple Health Export</h2>
      <p class="text-muted text-sm mb-4">
        Export your data from the Health app: <strong>Health → Profile → Export All Health Data</strong>.<br>
        Unzip the file and upload <code>apple_health_export/export.xml</code> here.
      </p>
    <?php else: ?>
      <h2 class="fw-bold mb-1" style="font-size:1.05rem;">📱 Samsung Health Export</h2>
      <p class="text-muted text-sm mb-4">
        Export from Samsung Health app: <strong>More → Settings → Download personal data</strong>.<br>
        Find a CSV file named <code>com.samsung.shealth.exercise.*.csv</code> and upload it here.
      </p>
    <?php endif; ?>

    <form method="POST" action="<?= url('health/upload') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">
          <?= $isApple ? 'Apple Health XML file (export.xml)' : 'Samsung Health CSV file' ?>
        </label>
        <input type="file"
               class="form-control"
               name="health_file"
               accept="<?= $isApple ? '.xml,application/xml,text/xml' : '.csv,text/csv' ?>"
               required>
        <span class="form-hint">
          <?= $isApple ? 'Max 50 MB. Only activities (Run, Walk, Ride) will be imported.' : 'Max 10 MB. Exercise records only.' ?>
        </span>
      </div>
      <button type="submit" class="btn btn-primary">Import Activities</button>
      <a href="<?= url('profile/edit') ?>" class="btn btn-secondary" style="margin-left:8px;">Cancel</a>
    </form>
  </div>

  <div class="card">
    <h3 class="fw-bold mb-3" style="font-size:.95rem;">How it works</h3>
    <ul style="padding-left:20px; line-height:1.8; color:var(--text-muted); font-size:.875rem;">
      <li>Only <strong>Run</strong>, <strong>Walk</strong>, and <strong>Ride</strong> activities are imported.</li>
      <li>Duplicate activities (same date + type + distance) are skipped automatically.</li>
      <li>Your points will update immediately after import.</li>
      <li>You can re-upload at any time — duplicates will not be double-counted.</li>
    </ul>
  </div>

  <?php endif; ?>
</div>

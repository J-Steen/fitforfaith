<?php
/** @var array $user @var array $rank @var array $activities @var ?array $donation @var ?array $churchRank */
$isPaid    = (bool)$user['is_paid'];
$hasStrava = !empty($user['strava_athlete_id']);
?>

<div class="container" style="padding: 32px 20px;">

  <?php if (empty($user['email_verified_at'])): ?>
  <div class="alert" style="background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);
       border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.875rem;">
    <i class="fa-solid fa-envelope"></i> <strong><?= t('dash.verify_email') ?></strong> — <?= t('dash.verify_msg') ?>
    <strong><?= h($user['email']) ?></strong>.
    <a href="<?= url('resend-verification') ?>" style="color:#f59e0b;margin-left:8px;"><?= t('dash.resend') ?></a>
  </div>
  <?php endif; ?>

  <!-- Greeting -->
  <div class="mb-4">
    <h1 class="section-title">Hi, <?= h($user['first_name']) ?></h1>
    <p class="text-muted"><?= t('dash.subtitle', ['name' => h(APP_NAME)]) ?></p>
  </div>

  <!-- Status bar -->
  <div class="card mb-4" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
    <div>
      <div class="text-xs text-muted mb-1"><?= t('dash.reg_fee') ?></div>
      <?php if ($isPaid): ?>
        <span class="status-badge status-paid"><i class="fa-solid fa-check"></i> <?= t('dash.paid') ?></span>
      <?php else: ?>
        <span class="status-badge status-unpaid"><i class="fa-solid fa-triangle-exclamation"></i> <?= t('dash.not_paid') ?></span>
      <?php endif; ?>
    </div>
    <div>
      <div class="text-xs text-muted mb-1"><?= t('dash.strava') ?></div>
      <span class="status-badge <?= $hasStrava ? 'status-connected' : 'status-disconnected' ?>">
        <i class="fa-solid fa-bolt"></i> <?= $hasStrava ? t('dash.connected') : t('dash.not_linked') ?>
      </span>
    </div>
    <?php if ($user['church_id']): ?>
    <div>
      <div class="text-xs text-muted mb-1"><?= t('dash.your_church') ?></div>
      <span class="church-badge">⛪ <?= h(\App\Models\Church::findById((int)$user['church_id'])['name'] ?? 'Unknown') ?></span>
    </div>
    <?php endif; ?>
    <div style="margin-left:auto; display:flex; gap:8px; flex-wrap:wrap;">
      <?php if (!$isPaid): ?>
        <a href="<?= url('donate') ?>" class="btn btn-primary btn-sm"><?= t('dash.pay_fee') ?></a>
      <?php endif; ?>
      <?php if (!$hasStrava): ?>
        <a href="<?= url('strava/connect-page') ?>" class="btn btn-sm" style="background:#FC4C02; color:#fff;"><?= t('dash.connect_strava') ?></a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats grid -->
  <div class="dashboard-grid mb-4">
    <!-- Points card -->
    <div class="card card-gradient">
      <div class="text-xs text-muted mb-1 fw-bold" style="text-transform:uppercase; letter-spacing:.05em;"><?= t('dash.your_points') ?></div>
      <div style="font-size:3rem; font-weight:900; line-height:1;" class="gradient-text">
        <?= fmt_points((int)$rank['total_points']) ?>
      </div>
      <div class="text-muted text-sm mt-2"><?= t('dash.total_earned') ?></div>
      <?php if ($rank['user_rank']): ?>
        <div class="mt-3 text-sm">
          <?= t('dash.ind_rank') ?> <strong class="gradient-text"><?= ordinal((int)$rank['user_rank']) ?></strong>
        </div>
      <?php endif; ?>
    </div>

    <!-- Activities card -->
    <div class="card">
      <div class="text-xs text-muted mb-2 fw-bold" style="text-transform:uppercase; letter-spacing:.05em;">Activity Breakdown</div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div>
          <div class="text-xs text-muted"><i class="fa-solid fa-person-running"></i> Running</div>
          <div class="fw-bold gradient-text"><?= fmt_points((int)$rank['run_points']) ?> pts</div>
        </div>
        <div>
          <div class="text-xs text-muted"><i class="fa-solid fa-person-walking"></i> Walking</div>
          <div class="fw-bold gradient-text"><?= fmt_points((int)$rank['walk_points']) ?> pts</div>
        </div>
        <div>
          <div class="text-xs text-muted"><i class="fa-solid fa-person-biking"></i> Cycling</div>
          <div class="fw-bold gradient-text"><?= fmt_points((int)$rank['ride_points']) ?> pts</div>
        </div>
        <div>
          <div class="text-xs text-muted">Total Activities</div>
          <div class="fw-bold"><?= (int)$rank['activity_count'] ?></div>
        </div>
      </div>
    </div>

    <!-- Church card -->
    <?php if ($churchRank): ?>
    <div class="card">
      <div class="text-xs text-muted mb-2 fw-bold" style="text-transform:uppercase; letter-spacing:.05em;"><i class="fa-solid fa-church"></i> <?= t('dash.church_standing') ?></div>
      <div class="fw-bold" style="font-size:1.1rem; margin-bottom:6px;"><?= h($churchRank['name']) ?></div>
      <?php if ($churchRank['church_rank']): ?>
        <div class="text-sm mb-2"><?= t('dash.church_rank') ?> <strong class="gradient-text"><?= ordinal((int)$churchRank['church_rank']) ?></strong></div>
      <?php endif; ?>
      <div class="text-sm text-muted"><?= fmt_points((int)$churchRank['total_points']) ?> <?= t('dash.church_points') ?></div>
      <a href="<?= url('leaderboard?tab=church') ?>" class="btn btn-secondary btn-sm mt-3">Church Leaderboard</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Next steps -->
  <?php if (!$isPaid || !$hasStrava): ?>
  <div class="card mb-4" style="border-color: rgba(245,158,11,0.3); background: rgba(245,158,11,0.06);">
    <h3 class="fw-bold mb-3"><i class="fa-solid fa-bolt"></i> <?= t('dash.setup_title') ?></h3>
    <div style="display:flex; flex-direction:column; gap:10px;">
      <?php if (!$isPaid): ?>
        <div style="display:flex; align-items:center; gap:12px;">
          <div style="width:28px; height:28px; border-radius:50%; background:rgba(245,158,11,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">1</div>
          <div class="flex-1">
            <div class="fw-bold text-sm">Pay Registration Fee (<?= REGISTRATION_FEE_DISPLAY ?>)</div>
            <div class="text-xs text-muted">Required to participate in the challenge</div>
          </div>
          <a href="<?= url('donate') ?>" class="btn btn-primary btn-sm">Pay Now</a>
        </div>
      <?php else: ?>
        <div style="display:flex; align-items:center; gap:12px; opacity:0.5;">
          <div style="width:28px; height:28px; border-radius:50%; background:rgba(16,185,129,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#6ee7b7;"><i class="fa-solid fa-check"></i></div>
          <div class="fw-bold text-sm">Registration Fee Paid</div>
        </div>
      <?php endif; ?>
      <?php if (!$hasStrava): ?>
        <div style="display:flex; align-items:center; gap:12px;">
          <div style="width:28px; height:28px; border-radius:50%; background:rgba(252,76,2,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">2</div>
          <div class="flex-1">
            <div class="fw-bold text-sm">Connect Your Strava Account</div>
            <div class="text-xs text-muted">Activities sync automatically once connected</div>
          </div>
          <a href="<?= url('strava/connect-page') ?>" class="strava-btn" style="padding:8px 16px; font-size:0.8rem;">Connect</a>
        </div>
      <?php else: ?>
        <div style="display:flex; align-items:center; gap:12px; opacity:0.5;">
          <div style="width:28px; height:28px; border-radius:50%; background:rgba(16,185,129,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#6ee7b7;"><i class="fa-solid fa-check"></i></div>
          <div class="fw-bold text-sm">Strava Connected</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>


  <!-- Recent activities -->
  <div class="mb-4">
    <div class="flex-between mb-3">
      <h3 class="fw-bold"><?= t('dash.recent') ?></h3>
      <a href="<?= url('leaderboard') ?>" class="btn btn-secondary btn-sm">Leaderboard →</a>
    </div>
    <?php if (empty($activities)): ?>
      <div class="card text-center" style="padding:40px;">
        <div style="font-size:3rem; margin-bottom:12px;"><i class="fa-solid fa-person-running"></i></div>
        <div class="fw-bold mb-2"><?= t('dash.no_activities') ?></div>
      </div>
    <?php else: ?>
      <?php foreach ($activities as $act): ?>
        <div class="activity-item">
          <div class="activity-icon"><?= activity_icon($act['activity_type']) ?></div>
          <div class="activity-info">
            <div class="activity-name"><?= h($act['name'] ?: activity_label($act['activity_type'])) ?></div>
            <div class="activity-meta">
              <?= fmt_km($act['distance_meters']) ?>
              &middot; <?= fmt_duration($act['moving_time_sec']) ?>
              &middot; <?= date('j M', strtotime($act['start_date'])) ?>
            </div>
          </div>
          <div class="activity-pts">+<?= (int)$act['points_awarded'] ?> pts</div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

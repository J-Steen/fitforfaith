<?php
/** @var array $individuals @var array $churches @var array $runners @var array $walkers @var array $cyclists @var ?array $leadingChurch @var array $stats @var string $tab */
$activityTab = in_array($tab, ['run','walk','ride']) ? $tab : null;
?>

<div class="container" style="padding: 32px 20px;">
  <div class="mb-4">
    <h1 class="section-title"><?= t('lb.title') ?></h1>
    <p class="text-muted"><?= t('lb.updated') ?> &middot; <?= number_format($stats['users']) ?> <?= t('lb.participants') ?></p>
  </div>

  <!-- Leading church banner -->
  <?php if ($leadingChurch): ?>
    <div class="leading-church mb-4 fade-in">
      <div class="leading-church-label"><i class="fa-solid fa-trophy"></i> <?= t('lb.leading') ?></div>
      <div class="leading-church-name"><?= h($leadingChurch['name']) ?></div>
      <p class="text-muted mt-2">
        <strong class="gradient-text"><?= fmt_points((int)$leadingChurch['total_points']) ?></strong> <?= t('lb.points') ?>
        &nbsp;&middot;&nbsp;
        <?= (int)$leadingChurch['member_count'] ?> <?= t('lb.members') ?>
      </p>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="leaderboard-tabs">
    <a href="?tab=individual" class="tab-btn <?= $tab === 'individual' || !$activityTab && $tab !== 'church' ? 'active' : '' ?>"><i class="fa-solid fa-medal"></i> <?= t('lb.tab_individual') ?></a>
    <a href="?tab=church"     class="tab-btn <?= $tab === 'church'     ? 'active' : '' ?>"><i class="fa-solid fa-church"></i> <?= t('lb.tab_churches') ?></a>
    <a href="?tab=run"        class="tab-btn <?= $tab === 'run'        ? 'active' : '' ?>"><i class="fa-solid fa-person-running"></i> Running</a>
    <a href="?tab=walk"       class="tab-btn <?= $tab === 'walk'       ? 'active' : '' ?>"><i class="fa-solid fa-person-walking"></i> Walking</a>
    <a href="?tab=ride"       class="tab-btn <?= $tab === 'ride'       ? 'active' : '' ?>"><i class="fa-solid fa-person-biking"></i> Cycling</a>
  </div>

  <?php if ($activityTab): ?>
    <?php
    $activityData  = ['run' => $runners, 'walk' => $walkers, 'ride' => $cyclists][$activityTab];
    $activityIcons = ['run' => 'fa-person-running', 'walk' => 'fa-person-walking', 'ride' => 'fa-person-biking'];
    $activityLabel = ['run' => 'Running', 'walk' => 'Walking', 'ride' => 'Cycling'][$activityTab];
    $myId = auth_check() ? (auth_user()['id'] ?? null) : null;
    ?>
    <?php if (empty($activityData)): ?>
      <div class="card text-center" style="padding:60px;">
        <div style="font-size:3rem; margin-bottom:12px;"><i class="fa-solid <?= $activityIcons[$activityTab] ?>"></i></div>
        <div class="fw-bold">No <?= $activityLabel ?> points yet</div>
        <p class="text-muted text-sm mt-2">Be the first to log a <?= strtolower($activityLabel) ?> activity!</p>
      </div>
    <?php else: ?>
      <div class="leaderboard-list">
        <?php foreach ($activityData as $i => $u): ?>
          <div class="leaderboard-row <?= $i < 3 ? 'top-' . ($i+1) : '' ?> <?= $myId == $u['id'] ? 'highlight' : '' ?> fade-in">
            <div class="rank-badge <?= ['gold','silver','bronze'][$i] ?? '' ?>">
              <?= $i < 3 ? rank_medal($i+1) : '#' . ($i+1) ?>
            </div>
            <div class="leaderboard-info">
              <div class="leaderboard-name">
                <?= h($u['first_name'] . ' ' . substr($u['last_name'], 0, 1)) ?>.
                <?php if ($myId == $u['id']): ?>
                  <span class="badge badge-purple" style="margin-left:6px;"><?= t('lb.you') ?></span>
                <?php endif; ?>
              </div>
              <?php if ($u['church_name']): ?>
                <div class="leaderboard-sub"><i class="fa-solid fa-church"></i> <?= h($u['church_name']) ?></div>
              <?php endif; ?>
            </div>
            <div class="text-right">
              <div class="leaderboard-points"><?= fmt_points((int)$u['activity_points']) ?></div>
              <div class="points-label"><?= $activityLabel ?> pts</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php elseif ($tab === 'church'): ?>
    <!-- Church leaderboard -->
    <?php if (empty($churches)): ?>
      <div class="card text-center" style="padding:60px;">
        <div style="font-size:3rem; margin-bottom:12px;"><i class="fa-solid fa-church"></i></div>
        <div class="fw-bold"><?= t('lb.no_church_pts') ?></div>
        <p class="text-muted text-sm mt-2"><?= t('lb.no_church_hint') ?></p>
      </div>
    <?php else: ?>
      <div class="leaderboard-list">
        <?php foreach ($churches as $i => $church): ?>
          <div class="leaderboard-row <?= $i < 3 ? 'top-' . ($i+1) : '' ?> fade-in">
            <div class="rank-badge <?= ['gold','silver','bronze'][$i] ?? '' ?>">
              <?= rank_medal($i+1) ?>
            </div>
            <div class="leaderboard-info">
              <div class="leaderboard-name"><?= h($church['name']) ?></div>
              <div class="leaderboard-sub">
                <?= h($church['city'] ?? '') ?> &middot;
                <?= (int)$church['member_count'] ?> <?= t('lb.members') ?> &middot;
                <?= t('lb.avg') ?> <?= number_format($church['avg_points']) ?> pts
              </div>
            </div>
            <div class="text-right">
              <div class="leaderboard-points"><?= fmt_points((int)$church['total_points']) ?></div>
              <div class="points-label"><?= t('lb.points') ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Individual leaderboard -->
    <?php
    // Highlight current user if logged in
    $myId = auth_check() ? (auth_user()['id'] ?? null) : null;
    ?>

    <?php if (empty($individuals)): ?>
      <div class="card text-center" style="padding:60px;">
        <div style="font-size:3rem; margin-bottom:12px;"><i class="fa-solid fa-person-running"></i></div>
        <div class="fw-bold"><?= t('lb.no_ind_pts') ?></div>
        <p class="text-muted text-sm mt-2"><?= t('lb.no_ind_hint') ?></p>
        <?php if (!auth_check()): ?>
          <a href="<?= url('register') ?>" class="btn btn-primary mt-3"><?= t('lb.join_now') ?></a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <!-- Top 3 podium (for screens > 600px) -->
      <?php $top3 = array_slice($individuals, 0, 3); ?>
      <div class="leaderboard-list">
        <?php foreach ($individuals as $i => $u): ?>
          <div class="leaderboard-row <?= $i < 3 ? 'top-' . ($i+1) : '' ?> <?= $myId == $u['id'] ? 'highlight' : '' ?> fade-in">
            <div class="rank-badge <?= ['gold','silver','bronze'][$i] ?? '' ?>">
              <?= $i < 3 ? rank_medal($i+1) : '#' . ($i+1) ?>
            </div>
            <div class="leaderboard-info">
              <div class="leaderboard-name">
                <?= h($u['first_name'] . ' ' . substr($u['last_name'],0,1)) ?>.
                <?php if ($myId == $u['id']): ?>
                  <span class="badge badge-purple" style="margin-left:6px;"><?= t('lb.you') ?></span>
                <?php endif; ?>
              </div>
              <?php if ($u['church_name']): ?>
                <div class="leaderboard-sub"><i class="fa-solid fa-church"></i> <?= h($u['church_name']) ?></div>
              <?php endif; ?>
            </div>
            <div class="text-right">
              <div class="leaderboard-points"><?= fmt_points((int)$u['total_points']) ?></div>
              <div class="points-label"><?= (int)$u['activity_count'] ?> <?= t('lb.activities') ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- If logged in and user not in top — show their position -->
    <?php if ($myId): ?>
      <?php
      $myRank = null;
      foreach ($individuals as $i => $u) {
          if ($u['id'] == $myId) { $myRank = $i+1; break; }
      }
      if (!$myRank): ?>
        <div class="card mt-3" style="border-color:rgba(124,58,237,0.3);">
          <div class="text-center text-muted text-sm">
            <?= t('lb.not_in_lb') ?>
            <?= !auth_user()['strava_athlete_id']
              ? '<br><a href="' . url('strava/connect-page') . '" class="btn btn-primary btn-sm mt-2">' . t('dash.connect_strava') . '</a>'
              : '<br>' . t('lb.log_activity') ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  <?php endif; ?>
</div>

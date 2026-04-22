<?php /** @var array $stats @var array $topUsers @var array $topChurches @var ?array $leadingChurch @var int $totalRaised */ ?>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="hero-badge"><i class="fa-solid fa-church"></i> <?= t('home.badge') ?></div>
    <h1 class="hero-title">
      <?= t('home.hero_line1') ?><br>
      <span class="gradient-text"><?= t('home.hero_line2') ?></span>
    </h1>
    <p class="hero-subtitle"><?= t('home.hero_sub', ['name' => h(APP_NAME)]) ?></p>
    <div class="hero-actions">
      <?php if (!auth_check()): ?>
        <a href="<?= url('register') ?>" class="btn btn-primary btn-lg"><?= t('home.join') ?></a>
        <a href="<?= url('leaderboard') ?>" class="btn btn-secondary btn-lg"><?= t('home.view_lb') ?></a>
      <?php else: ?>
        <a href="<?= url('dashboard') ?>" class="btn btn-primary btn-lg"><?= t('home.my_dashboard') ?></a>
        <a href="<?= url('leaderboard') ?>" class="btn btn-secondary btn-lg"><?= t('home.leaderboard') ?></a>
      <?php endif; ?>
      <a href="<?= url('contact') ?>" class="btn btn-secondary btn-lg"><i class="fa-solid fa-headset"></i> <?= t('footer.contact') ?></a>
    </div>
  </div>
</section>

<!-- Stats -->
<section class="section-sm">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-card fade-in">
        <div class="stat-value"><?= number_format($stats['users']) ?></div>
        <div class="stat-label"><?= t('home.participants') ?></div>
      </div>
      <div class="stat-card fade-in">
        <div class="stat-value"><?= number_format($stats['churches']) ?></div>
        <div class="stat-label"><?= t('home.churches') ?></div>
      </div>
      <div class="stat-card fade-in">
        <div class="stat-value"><?= number_format($stats['points']) ?></div>
        <div class="stat-label"><?= t('home.total_points') ?></div>
      </div>
      <div class="stat-card fade-in">
        <div class="stat-value"><?= fmt_money($totalRaised) ?></div>
        <div class="stat-label"><?= t('home.raised') ?></div>
      </div>
      <div class="stat-card fade-in">
        <div class="stat-value"><?= number_format($stats['activities']) ?></div>
        <div class="stat-label"><?= t('home.activities') ?></div>
      </div>
    </div>
  </div>
</section>

<!-- Leading Church -->
<?php if ($leadingChurch): ?>
<section class="section-sm">
  <div class="container container-md">
    <div class="leading-church fade-in">
      <div class="leading-church-label"><i class="fa-solid fa-trophy"></i> <?= t('home.leading') ?></div>
      <div class="leading-church-name"><?= h($leadingChurch['name']) ?></div>
      <p class="text-muted mt-2">
        <strong class="gradient-text"><?= fmt_points((int)$leadingChurch['total_points']) ?></strong> <?= t('lb.points') ?>
        &nbsp;&middot;&nbsp;
        <?= (int)$leadingChurch['member_count'] ?> <?= t('lb.members') ?>
      </p>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Top Individuals Preview -->
<?php if ($topUsers): ?>
<section class="section-sm">
  <div class="container container-md">
    <div class="flex-between mb-4">
      <div>
        <h2 class="section-title"><?= t('home.top_runners') ?></h2>
        <p class="section-sub"><?= t('home.leading_charge') ?></p>
      </div>
      <a href="<?= url('leaderboard') ?>" class="btn btn-secondary btn-sm"><?= t('home.full_lb') ?></a>
    </div>
    <div class="leaderboard-list">
      <?php foreach ($topUsers as $i => $u): ?>
        <div class="leaderboard-row <?= $i < 3 ? 'top-' . ($i+1) : '' ?> fade-in">
          <div class="rank-badge <?= ['gold','silver','bronze'][$i] ?? '' ?>">
            <?= rank_medal($i+1) ?>
          </div>
          <div class="leaderboard-info">
            <div class="leaderboard-name"><?= h($u['first_name'] . ' ' . substr($u['last_name'],0,1)) ?>.</div>
            <?php if ($u['church_name']): ?>
              <div class="leaderboard-sub"><i class="fa-solid fa-church"></i> <?= h($u['church_name']) ?></div>
            <?php endif; ?>
          </div>
          <div class="text-right">
            <div class="leaderboard-points"><?= fmt_points((int)$u['total_points']) ?></div>
            <div class="points-label"><?= t('lb.points') ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- How it Works -->
<section class="section">
  <div class="container container-md">
    <h2 class="section-title text-center"><?= t('home.how_works') ?></h2>
    <p class="section-sub text-center"><?= t('home.how_sub') ?></p>
    <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-top:8px;">
      <div class="card card-gradient text-center">
        <div style="font-size:2.5rem; margin-bottom:12px;"><i class="fa-solid fa-user-plus"></i></div>
        <h3 class="fw-bold mb-2"><?= t('home.step1_title') ?></h3>
        <p class="text-muted text-sm"><?= t('home.step1_desc', ['fee' => REGISTRATION_FEE_DISPLAY]) ?></p>
      </div>
      <div class="card card-gradient text-center">
        <div style="font-size:2.5rem; margin-bottom:12px;"><i class="fa-solid fa-link"></i></div>
        <h3 class="fw-bold mb-2"><?= t('home.step2_title') ?></h3>
        <p class="text-muted text-sm"><?= t('home.step2_desc') ?></p>
      </div>
      <div class="card card-gradient text-center">
        <div style="font-size:2.5rem; margin-bottom:12px;"><i class="fa-solid fa-trophy"></i></div>
        <h3 class="fw-bold mb-2"><?= t('home.step3_title') ?></h3>
        <p class="text-muted text-sm"><?= t('home.step3_desc') ?></p>
      </div>
    </div>
  </div>
</section>

<!-- Points Guide -->
<section class="section-sm">
  <div class="container container-md">
    <div class="card">
      <h3 class="fw-bold mb-3"><?= t('home.pts_title') ?></h3>
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap:12px;">
        <div style="text-align:center; padding:16px; background:var(--glass); border-radius:var(--radius-sm);">
          <div style="font-size:2rem; margin-bottom:8px;"><i class="fa-solid fa-person-running"></i></div>
          <div class="fw-bold" style="font-size:1.4rem; color:var(--primary-light);">10 pts</div>
          <div class="text-muted text-sm"><?= t('home.pts_running') ?></div>
        </div>
        <div style="text-align:center; padding:16px; background:var(--glass); border-radius:var(--radius-sm);">
          <div style="font-size:2rem; margin-bottom:8px;"><i class="fa-solid fa-person-walking"></i></div>
          <div class="fw-bold" style="font-size:1.4rem; color:var(--primary-light);">5 pts</div>
          <div class="text-muted text-sm"><?= t('home.pts_walking') ?></div>
        </div>
        <div style="text-align:center; padding:16px; background:var(--glass); border-radius:var(--radius-sm);">
          <div style="font-size:2rem; margin-bottom:8px;"><i class="fa-solid fa-person-biking"></i></div>
          <div class="fw-bold" style="font-size:1.4rem; color:var(--primary-light);">3 pts</div>
          <div class="text-muted text-sm"><?= t('home.pts_cycling') ?></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<?php if (!auth_check()): ?>
<section class="section">
  <div class="container text-center">
    <h2 class="section-title"><?= t('home.cta_title') ?></h2>
    <p class="text-muted mb-4"><?= t('home.cta_sub') ?></p>
    <a href="<?= url('register') ?>" class="btn btn-primary btn-lg"><?= t('home.cta_btn', ['fee' => REGISTRATION_FEE_DISPLAY]) ?></a>
  </div>
</section>
<?php endif; ?>

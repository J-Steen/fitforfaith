<?php /** @var array $user */ ?>
<div class="container container-sm" style="padding: 48px 20px; text-align:center;">
  <div class="card card-gradient fade-in" style="padding:48px 36px;">
    <div style="font-size:4rem; margin-bottom:20px;"><i class="fa-solid fa-bolt" style="color:#FC4C02;"></i></div>
    <h1 class="section-title">Connect Strava</h1>
    <p class="text-muted mt-2 mb-4">
      Connect your Strava account to automatically earn points for every run, walk, and ride.
      Your activities sync automatically — no manual logging needed.
    </p>

    <div class="card" style="text-align:left; margin-bottom:28px;">
      <h3 class="fw-bold mb-3 text-sm" style="text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);">What gets synced</h3>
      <div style="display:flex; flex-direction:column; gap:10px;">
        <div style="display:flex; gap:10px; align-items:center;">
          <span style="font-size:1.3rem;"><i class="fa-solid fa-person-running"></i></span>
          <div><strong>Running</strong> <span class="text-muted text-sm">— 10 points per km</span></div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
          <span style="font-size:1.3rem;"><i class="fa-solid fa-person-walking"></i></span>
          <div><strong>Walking</strong> <span class="text-muted text-sm">— 5 points per km</span></div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
          <span style="font-size:1.3rem;"><i class="fa-solid fa-person-biking"></i></span>
          <div><strong>Cycling</strong> <span class="text-muted text-sm">— 3 points per km</span></div>
        </div>
      </div>
    </div>

    <?php if (!empty($user['strava_athlete_id'])): ?>
      <div class="alert alert-success mb-3"><i class="fa-solid fa-check"></i> Strava is already connected</div>
      <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
        <a href="<?= url('dashboard') ?>" class="btn btn-primary">Back to Dashboard</a>
        <form method="POST" action="<?= url('strava/disconnect') ?>" style="display:inline;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-secondary"
                  onclick="return confirm('Disconnect Strava? Your points history will be kept.')">
            Disconnect
          </button>
        </form>
      </div>
    <?php else: ?>
      <a href="<?= url('strava/connect') ?>" class="strava-btn" style="display:inline-flex; margin:0 auto;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/>
        </svg>
        Connect with Strava
      </a>
      <p class="text-muted text-xs mt-3">
        You'll be redirected to Strava to authorize access to your activities.
      </p>
    <?php endif; ?>
  </div>
</div>

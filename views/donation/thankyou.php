<div class="container container-sm text-center" style="padding: 80px 20px;">
  <div class="card fade-in" style="padding:48px 32px;">
    <div style="font-size:4rem; margin-bottom:20px;"><i class="fa-solid fa-circle-check" style="color:#6ee7b7;"></i></div>
    <h1 class="section-title mb-2">Thank You!</h1>
    <p class="text-muted mb-4">
      Your payment is being processed. Once confirmed, your account will be marked as paid
      and you'll be ready to start earning points.
    </p>
    <?php if (auth_check() && auth_user()['is_paid']): ?>
      <div class="alert alert-success mb-4"><i class="fa-solid fa-check"></i> Payment confirmed! You're in the challenge.</div>
    <?php else: ?>
      <div class="alert alert-info mb-4"><i class="fa-solid fa-circle-info"></i> Payment confirmation may take a few minutes.</div>
    <?php endif; ?>
    <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
      <a href="<?= url('dashboard') ?>" class="btn btn-primary">Go to Dashboard</a>
      <a href="<?= url('strava/connect-page') ?>" class="strava-btn" style="padding:12px 24px;">Connect Strava</a>
    </div>
  </div>
</div>

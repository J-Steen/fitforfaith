<?php /** @var array $user @var ?array $existing */ ?>
<div class="container container-sm" style="padding: 48px 20px;">
  <div class="card fade-in" style="padding:40px 32px;">
    <h1 class="section-title mb-2">Registration Fee</h1>
    <p class="text-muted mb-4">A once-off payment to participate in the <?= h(APP_NAME) ?> challenge.</p>

    <?php if ($user['is_paid']): ?>
      <div class="alert alert-success"><i class="fa-solid fa-check"></i> Your registration fee is already paid. You are all set!</div>
      <a href="<?= url('dashboard') ?>" class="btn btn-primary">Back to Dashboard</a>
    <?php else: ?>
      <div class="card card-gradient mb-4">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div class="fw-bold"><?= h(APP_NAME) ?> Registration</div>
            <div class="text-muted text-sm">One-time entry fee</div>
          </div>
          <div class="stat-value" style="font-size:2rem;"><?= REGISTRATION_FEE_DISPLAY ?></div>
        </div>
      </div>

      <ul class="text-sm text-muted mb-4" style="padding-left:20px; display:flex; flex-direction:column; gap:6px;">
        <li>Access to the challenge leaderboard</li>
        <li>Points earned go toward your church's total</li>
        <li>Certificate of completion</li>
        <li>Contribution to the fundraiser goal</li>
      </ul>

      <form method="POST" action="<?= url('donate') ?>">
        <?= csrf_field() ?>
        <div class="card mb-4" style="padding:16px;">
          <div class="text-sm text-muted mb-1">Paying as</div>
          <div class="fw-bold"><?= h($user['first_name'] . ' ' . $user['last_name']) ?></div>
          <div class="text-sm text-muted"><?= h($user['email']) ?></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          Pay <?= REGISTRATION_FEE_DISPLAY ?> via PayFast
        </button>
        <p class="text-center text-xs text-muted mt-3">
          Secure payment via PayFast. South African payment gateway.
        </p>
      </form>
    <?php endif; ?>
  </div>
</div>

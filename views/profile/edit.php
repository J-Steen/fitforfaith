<?php /** @var array $user @var array $churches @var array $errors */ ?>
<div class="container container-sm" style="padding: 32px 20px;">
  <h1 class="section-title mb-4"><?= t('profile.title') ?></h1>

  <!-- Profile update -->
  <div class="card mb-4">
    <h2 class="fw-bold mb-4" style="font-size:1.1rem;"><?= t('profile.personal') ?></h2>
    <form method="POST" action="<?= url('profile/edit') ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="_method" value="PUT">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label"><?= t('profile.first_name') ?></label>
          <input type="text" class="form-control" name="first_name"
                 value="<?= h($user['first_name']) ?>" required>
          <?php if (isset($errors['first_name'])): ?>
            <span class="form-error"><?= h($errors['first_name']) ?></span>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label"><?= t('profile.last_name') ?></label>
          <input type="text" class="form-control" name="last_name"
                 value="<?= h($user['last_name']) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('profile.email') ?></label>
        <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled>
        <span class="form-hint"><?= t('profile.email_hint') ?></span>
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('profile.phone') ?></label>
        <input type="tel" class="form-control" name="phone" value="<?= h($user['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('profile.church') ?></label>
        <select class="form-control form-select" name="church_id">
          <option value="">— <?= t('profile.church') ?> —</option>
          <?php foreach ($churches as $church): ?>
            <option value="<?= $church['id'] ?>" <?= $user['church_id'] == $church['id'] ? 'selected' : '' ?>>
              <?= h($church['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('profile.language') ?></label>
        <select class="form-control form-select" name="language">
          <?php foreach (\App\Core\Lang::LABELS as $code => $label): ?>
            <option value="<?= $code ?>" <?= ($user['language'] ?? 'en') === $code ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><?= t('profile.save') ?></button>
    </form>
  </div>

  <!-- Change password -->
  <div class="card mb-4">
    <h2 class="fw-bold mb-4" style="font-size:1.1rem;"><?= t('profile.change_pw') ?></h2>
    <form method="POST" action="<?= url('profile/password') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label"><?= t('profile.current_pw') ?></label>
        <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
               name="current_password" required>
        <?php if (isset($errors['current_password'])): ?>
          <span class="form-error"><?= h($errors['current_password']) ?></span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('profile.new_pw') ?></label>
        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
               name="password" minlength="8" required>
        <?php if (isset($errors['password'])): ?>
          <span class="form-error"><?= h($errors['password']) ?></span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('profile.confirm_pw') ?></label>
        <input type="password" class="form-control" name="password_confirmation" required>
      </div>
      <button type="submit" class="btn btn-secondary"><?= t('profile.update_pw') ?></button>
    </form>
  </div>

  <!-- Strava Connection -->
  <div class="card">
    <h2 class="fw-bold mb-1" style="font-size:1.1rem;"><i class="fa-solid fa-bolt"></i> <?= t('profile.strava_title') ?></h2>
    <p class="text-muted text-sm mb-4"><?= t('profile.strava_hint') ?></p>

    <?php if ($user['strava_athlete_id']): ?>
      <div class="alert alert-success mb-3" style="font-size:.875rem;">
        <i class="fa-solid fa-check"></i> <?= t('profile.strava_connected') ?> <?= h($user['strava_athlete_id']) ?>
        <?= $user['strava_connected_at'] ? ' (' . t('profile.strava_since') . ' ' . date('j M Y', strtotime($user['strava_connected_at'])) . ')' : '' ?>
      </div>
      <form method="POST" action="<?= url('strava/disconnect') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger btn-sm"
                onclick="return confirm('<?= t('profile.strava_disconnect_confirm') ?>')">
          <?= t('profile.strava_disconnect') ?>
        </button>
      </form>
    <?php else: ?>
      <p class="text-muted text-sm mb-3"><?= t('profile.strava_not_linked') ?></p>
      <a href="<?= url('strava/connect-page') ?>" class="strava-btn" style="font-size:.875rem; padding:10px 20px;">
        <?= t('profile.strava_connect') ?>
      </a>
    <?php endif; ?>
  </div>
</div>

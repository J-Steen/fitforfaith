<?php /** @var array $errors @var string $token */ ?>
<div class="auth-page">
  <div class="auth-card fade-in">
    <div class="auth-logo">
      <a href="<?= url() ?>" class="nav-logo" style="font-size:1.5rem;"><?= h(APP_NAME) ?></a>
    </div>
    <h1 class="auth-title"><?= t('reset.title') ?></h1>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><?= h(reset($errors)) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('reset-password/' . h($token)) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label"><?= t('reset.new_pw') ?></label>
        <input type="password" class="form-control" name="password" minlength="8" required autofocus>
        <span class="form-hint"><?= t('reset.pw_hint') ?></span>
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('reset.confirm_pw') ?></label>
        <input type="password" class="form-control" name="password_confirmation" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><?= t('reset.submit') ?></button>
    </form>
  </div>
</div>

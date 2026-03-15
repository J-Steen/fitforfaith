<div class="auth-page">
  <div class="auth-card fade-in">
    <div class="auth-logo">
      <a href="<?= url() ?>" class="nav-logo" style="font-size:1.5rem;"><?= h(APP_NAME) ?></a>
    </div>
    <h1 class="auth-title"><?= t('forgot.title') ?></h1>
    <p class="auth-subtitle"><?= t('forgot.subtitle') ?></p>

    <?php $s = get_flash('success'); if ($s): ?>
      <div class="alert alert-success"><?= h($s) ?></div>
    <?php endif; ?>
    <?php $e = get_flash('error'); if ($e): ?>
      <div class="alert alert-error"><?= h($e) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('forgot-password') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" for="email"><?= t('forgot.email') ?></label>
        <input type="email" class="form-control" id="email" name="email" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><?= t('forgot.submit') ?></button>
    </form>

    <div class="auth-footer">
      <a href="<?= url('login') ?>"><?= t('forgot.back') ?></a>
    </div>
  </div>
</div>

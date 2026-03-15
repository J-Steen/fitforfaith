<?php /** @var array $errors @var array $old */ ?>
<div class="auth-page">
  <div class="auth-card fade-in">
    <div class="auth-logo">
      <a href="<?= url() ?>" class="nav-logo" style="font-size:1.5rem;"><?= h(APP_NAME) ?></a>
    </div>
    <h1 class="auth-title"><?= t('login.title') ?></h1>
    <p class="auth-subtitle"><?= t('login.subtitle') ?></p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?= h(reset($errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('login') ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="email"><?= t('login.email') ?></label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="email" name="email" value="<?= h($old['email'] ?? '') ?>"
               required autocomplete="email" autofocus>
        <?php if (isset($errors['email'])): ?>
          <span class="form-error"><?= h($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password"><?= t('login.password') ?></label>
        <input type="password" class="form-control"
               id="password" name="password"
               required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg"><?= t('login.submit') ?></button>
    </form>

    <div class="auth-footer" style="margin-top:16px;">
      <a href="<?= url('forgot-password') ?>"><?= t('login.forgot') ?></a>
    </div>
    <div class="auth-divider"><span><?= t('login.or') ?></span></div>
    <div class="auth-footer">
      <?= t('login.no_account') ?> <a href="<?= url('register') ?>"><?= t('login.create') ?></a>
    </div>
  </div>
</div>

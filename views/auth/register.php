<?php /** @var array $errors @var array $old @var array $churches @var ?int $preselectedChurch */ ?>
<div class="auth-page" style="align-items:flex-start; padding-top:40px;">
  <div class="auth-card fade-in" style="max-width:540px;">
    <div class="auth-logo">
      <a href="<?= url() ?>" class="nav-logo" style="font-size:1.5rem;"><?= h(APP_NAME) ?></a>
    </div>
    <h1 class="auth-title"><?= t('register.title') ?></h1>
    <p class="auth-subtitle"><?= t('register.subtitle', ['fee' => REGISTRATION_FEE_DISPLAY]) ?></p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
          <div><?= h($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('register') ?>" novalidate>
      <?= csrf_field() ?>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label" for="first_name"><?= t('register.first_name') ?></label>
          <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                 id="first_name" name="first_name" value="<?= h($old['first_name'] ?? '') ?>"
                 required autocomplete="given-name">
          <?php if (isset($errors['first_name'])): ?>
            <span class="form-error"><?= h($errors['first_name']) ?></span>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="last_name"><?= t('register.last_name') ?></label>
          <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                 id="last_name" name="last_name" value="<?= h($old['last_name'] ?? '') ?>"
                 required autocomplete="family-name">
          <?php if (isset($errors['last_name'])): ?>
            <span class="form-error"><?= h($errors['last_name']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email"><?= t('register.email') ?></label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="email" name="email" value="<?= h($old['email'] ?? '') ?>"
               required autocomplete="email">
        <?php if (isset($errors['email'])): ?>
          <span class="form-error"><?= h($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="phone"><?= t('register.phone') ?> <span class="text-muted"><?= t('register.phone_opt') ?></span></label>
        <input type="tel" class="form-control" id="phone" name="phone"
               value="<?= h($old['phone'] ?? '') ?>" autocomplete="tel">
      </div>

      <div class="form-group">
        <label class="form-label" for="church_id"><?= t('register.church') ?></label>
        <select class="form-control form-select <?= isset($errors['church_id']) ? 'is-invalid' : '' ?>"
                id="church_id" name="church_id" required>
          <option value=""><?= t('register.church_pick') ?></option>
          <?php foreach ($churches as $church): ?>
            <option value="<?= $church['id'] ?>"
              <?= (($old['church_id'] ?? $preselectedChurch) == $church['id']) ? 'selected' : '' ?>>
              <?= h($church['name']) ?><?= $church['city'] ? ' (' . h($church['city']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['church_id'])): ?>
          <span class="form-error"><?= h($errors['church_id']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="language"><?= t('register.language') ?></label>
        <select class="form-control form-select" id="language" name="language">
          <?php foreach (\App\Core\Lang::LABELS as $code => $label): ?>
            <option value="<?= $code ?>" <?= (\App\Core\Lang::get() === $code) ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="password"><?= t('register.password') ?></label>
        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
               id="password" name="password" required autocomplete="new-password"
               minlength="8">
        <span class="form-hint"><?= t('register.pw_hint') ?></span>
        <?php if (isset($errors['password'])): ?>
          <span class="form-error"><?= h($errors['password']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirmation"><?= t('register.confirm_pw') ?></label>
        <input type="password" class="form-control" id="password_confirmation"
               name="password_confirmation" required autocomplete="new-password">
      </div>

      <div class="form-group">
        <div class="form-check">
          <input type="checkbox" class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>"
                 id="terms" name="terms" value="1" <?= !empty($old['terms']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="terms">
            <?= t('register.terms', ['name' => h(APP_NAME), 'fee' => REGISTRATION_FEE_DISPLAY]) ?>
          </label>
        </div>
        <?php if (isset($errors['terms'])): ?>
          <span class="form-error"><?= h($errors['terms']) ?></span>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <?= t('register.submit') ?>
      </button>
    </form>

    <div class="auth-footer">
      <?= t('register.have_account') ?> <a href="<?= url('login') ?>"><?= t('register.login_link') ?></a>
    </div>
  </div>
</div>

<?php /** @var array $errors @var array $old */ ?>

<section class="section">
  <div class="container container-md">
    <div class="card fade-in" style="max-width:600px; margin:0 auto;">

      <div style="text-align:center; margin-bottom:28px;">
        <div style="font-size:2.5rem; margin-bottom:12px;"><i class="fa-solid fa-headset"></i></div>
        <h1 class="section-title"><?= t('contact.title') ?></h1>
        <p class="text-muted"><?= t('contact.subtitle') ?></p>
      </div>

      <form method="POST" action="<?= url('contact') ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
          <label class="form-label" for="name"><?= t('contact.field_name') ?></label>
          <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                 id="name" name="name" value="<?= h($old['name'] ?? '') ?>"
                 required autocomplete="name">
          <?php if (isset($errors['name'])): ?>
            <span class="form-error"><?= h($errors['name']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="email"><?= t('contact.field_email') ?></label>
          <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                 id="email" name="email" value="<?= h($old['email'] ?? '') ?>"
                 required autocomplete="email">
          <?php if (isset($errors['email'])): ?>
            <span class="form-error"><?= h($errors['email']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="subject"><?= t('contact.field_subject') ?></label>
          <input type="text" class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                 id="subject" name="subject" value="<?= h($old['subject'] ?? '') ?>"
                 required>
          <?php if (isset($errors['subject'])): ?>
            <span class="form-error"><?= h($errors['subject']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="message"><?= t('contact.field_message') ?></label>
          <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                    id="message" name="message" rows="5"
                    required><?= h($old['message'] ?? '') ?></textarea>
          <?php if (isset($errors['message'])): ?>
            <span class="form-error"><?= h($errors['message']) ?></span>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
          <i class="fa-solid fa-paper-plane"></i> <?= t('contact.submit') ?>
        </button>
      </form>

    </div>
  </div>
</section>

<?php /** @var ?array $church @var array $errors @var array $old */ ?>

<div class="admin-page-header">
  <div class="admin-page-title"><?= isset($church) ? 'Edit Church' : 'Add Church' ?></div>
  <a href="<?= url('admin/churches') ?>" class="btn btn-secondary btn-sm">← Back</a>
</div>

<div class="card" style="max-width:600px;">
  <form method="POST"
        action="<?= isset($church) ? url('admin/churches/' . $church['id'] . '/update') : url('admin/churches') ?>">
    <?= csrf_field() ?>

    <div class="form-group">
      <label class="form-label">Church Name</label>
      <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
             name="name" value="<?= h($church['name'] ?? $old['name'] ?? '') ?>" required>
      <?php if (isset($errors['name'])): ?>
        <span class="form-error"><?= h($errors['name']) ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="form-label">City <span class="text-muted">(optional)</span></label>
      <input type="text" class="form-control" name="city"
             value="<?= h($church['city'] ?? $old['city'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Description <span class="text-muted">(optional)</span></label>
      <textarea class="form-control" name="description" rows="3"><?= h($church['description'] ?? '') ?></textarea>
    </div>

    <?php if (isset($church)): ?>
      <div class="form-group">
        <label class="form-check">
          <input type="checkbox" class="form-check-input" name="is_active" value="1"
                 <?= ($church['is_active'] ?? 1) ? 'checked' : '' ?>>
          <span class="form-check-label">Active (visible in registration form)</span>
        </label>
      </div>
    <?php endif; ?>

    <div style="display:flex; gap:10px;">
      <button type="submit" class="btn btn-primary">
        <?= isset($church) ? 'Update Church' : 'Create Church' ?>
      </button>
      <?php if (isset($church)): ?>
        <form method="POST" action="<?= url('admin/churches/' . $church['id'] . '/delete') ?>" style="display:inline;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger"
                  onclick="return confirm('Delete this church? All member church assignments will be cleared.')">
            Delete
          </button>
        </form>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php /** @var array $churches @var array $qrCodes */ ?>

<div class="admin-page-header">
  <div class="admin-page-title">Churches (<?= count($churches) ?>)</div>
  <a href="<?= url('admin/churches/new') ?>" class="btn btn-primary btn-sm">+ Add Church</a>
</div>

<div class="card mb-4">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>City</th>
          <th>Members</th>
          <th>Total Points</th>
          <th>Rank</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($churches as $c): ?>
          <tr>
            <td class="fw-bold"><?= h($c['name']) ?></td>
            <td class="text-muted"><?= h($c['city'] ?? '—') ?></td>
            <td><?= (int)$c['member_count'] ?></td>
            <td class="fw-bold gradient-text"><?= fmt_points((int)$c['total_points']) ?></td>
            <td><?= $c['church_rank'] ? '#' . $c['church_rank'] : '—' ?></td>
            <td><?= $c['is_active']
              ? '<span class="badge badge-green">Active</span>'
              : '<span class="badge badge-gray">Inactive</span>' ?></td>
            <td>
              <a href="<?= url('admin/churches/' . $c['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- QR Codes section -->
<div class="admin-page-header mt-4">
  <div class="admin-page-title">QR Codes</div>
</div>

<div class="card mb-3">
  <h3 class="fw-bold mb-3">Create New QR Code</h3>
  <form method="POST" action="<?= url('admin/churches/qr') ?>" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
    <?= csrf_field() ?>
    <div class="form-group mb-0">
      <label class="form-label">Label</label>
      <input type="text" class="form-control" name="label" placeholder="e.g. Poster A / WhatsApp group" style="min-width:200px;">
    </div>
    <div class="form-group mb-0">
      <label class="form-label">Preselect Church (optional)</label>
      <select class="form-control form-select" name="church_id" style="min-width:180px;">
        <option value="">No preselection</option>
        <?php foreach ($churches as $c): ?>
          <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group mb-0">
      <label class="form-label">Expires (optional)</label>
      <input type="date" class="form-control" name="expires_at">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Create QR</button>
  </form>
</div>

<?php if (!empty($qrCodes)): ?>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Label</th>
          <th>Church</th>
          <th>Scans</th>
          <th>Status</th>
          <th>Expires</th>
          <th>QR Image</th>
          <th>Registration URL</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($qrCodes as $qr): ?>
          <tr>
            <td class="fw-bold"><?= h($qr['label'] ?? $qr['token']) ?></td>
            <td class="text-sm"><?= h($qr['church_name'] ?? '—') ?></td>
            <td><?= (int)$qr['scans'] ?></td>
            <td><?= $qr['is_active']
              ? '<span class="badge badge-green">Active</span>'
              : '<span class="badge badge-gray">Inactive</span>' ?></td>
            <td class="text-muted text-sm"><?= $qr['expires_at'] ? date('j M Y', strtotime($qr['expires_at'])) : '—' ?></td>
            <td>
              <a href="<?= url('qr/' . $qr['token']) ?>" target="_blank" class="btn btn-secondary btn-sm">View QR</a>
            </td>
            <td>
              <input type="text" class="form-control text-xs" style="font-size:0.75rem; padding:4px 8px;"
                     value="<?= url('register/' . $qr['token']) ?>" readonly
                     onclick="this.select(); document.execCommand('copy'); this.blur();"
                     title="Click to copy">
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

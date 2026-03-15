<?php /** @var array $result @var array $churches @var array $filters */ ?>

<div class="admin-page-header">
  <div class="admin-page-title">Users (<?= number_format($result['total']) ?>)</div>
</div>

<!-- Filter bar -->
<form method="GET" action="<?= url('admin/users') ?>" class="filter-bar">
  <input type="text" class="form-control" name="search" placeholder="Search name or email…"
         value="<?= h($filters['search']) ?>" style="max-width:260px;">
  <select class="form-control form-select" name="church_id" style="max-width:200px;">
    <option value="">All Churches</option>
    <?php foreach ($churches as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $filters['church_id'] == $c['id'] ? 'selected' : '' ?>>
        <?= h($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select class="form-control form-select" name="paid" style="max-width:150px;">
    <option value="">Payment Status</option>
    <option value="1" <?= $filters['is_paid'] === 1 ? 'selected' : '' ?>>Paid</option>
    <option value="0" <?= $filters['is_paid'] === 0 ? 'selected' : '' ?>>Not Paid</option>
  </select>
  <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
  <a href="<?= url('admin/users') ?>" class="btn btn-secondary btn-sm">Reset</a>
</form>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Church</th>
          <th>Points</th>
          <th>Paid</th>
          <th>Strava</th>
          <th>Status</th>
          <th>Joined</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['data'] as $u): ?>
          <tr>
            <td>
              <span class="fw-bold"><?= h($u['first_name'] . ' ' . $u['last_name']) ?></span>
              <?php if ($u['role'] === 'admin'): ?>
                <span class="badge badge-purple ml-1">Admin</span>
              <?php endif; ?>
            </td>
            <td class="text-muted text-sm"><?= h($u['email']) ?></td>
            <td class="text-sm"><?= h($u['church_name'] ?? '—') ?></td>
            <td class="fw-bold gradient-text"><?= fmt_points((int)$u['total_points']) ?></td>
            <td><?= $u['is_paid']
              ? '<span class="badge badge-green"><i class="fa-solid fa-check"></i> Paid</span>'
              : '<span class="badge badge-yellow">Pending</span>' ?></td>
            <td>
              <?php if ($u['strava_athlete_id']): ?>
                <span class="badge badge-blue"><i class="fa-solid fa-bolt"></i> Connected</span>
              <?php else: ?>
                <span class="badge badge-gray">— Not linked</span>
              <?php endif; ?>
            </td>
            <td><?= $u['is_active']
              ? '<span class="badge badge-green">Active</span>'
              : '<span class="badge badge-red">Disabled</span>' ?></td>
            <td class="text-muted text-sm"><?= date('j M Y', strtotime($u['created_at'])) ?></td>
            <td style="white-space:nowrap;">
              <a href="<?= url('admin/users/' . $u['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">Edit</a>
              <form method="POST" action="<?= url('admin/users/' . $u['id'] . '/toggle-active') ?>" style="display:inline;"
                    onsubmit="return confirm('<?= $u['is_active'] ? 'Disable this user?' : 'Enable this user?' ?>')">
                <?= csrf_field() ?>
                <button class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                  <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($result['data'])): ?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:40px;">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($result['pages'] > 1): ?>
<div class="pagination">
  <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
    <a href="?page=<?= $p ?>&search=<?= urlencode($filters['search']) ?>&church_id=<?= $filters['church_id'] ?>"
       class="page-btn <?= $p == $result['page'] ? 'active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

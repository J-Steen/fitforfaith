<?php /** @var array $result @var int $total */ ?>

<div class="admin-page-header">
  <div class="admin-page-title">Payments (<?= number_format($result['total']) ?>)</div>
  <div class="fw-bold gradient-text" style="font-size:1.4rem;">Total: <?= fmt_money($total) ?></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>User</th>
          <th>Church</th>
          <th>Amount</th>
          <th>PayFast ID</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['data'] as $d): ?>
          <tr>
            <td class="fw-bold text-sm"><?= h(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?></td>
            <td class="text-muted text-sm"><?= h($d['church_name'] ?? '—') ?></td>
            <td class="fw-bold"><?= fmt_money($d['amount_cents']) ?></td>
            <td class="text-xs text-muted"><?= h($d['pf_payment_id'] ?? '—') ?></td>
            <td><?php
              switch ($d['status']) {
                case 'complete':  echo '<span class="badge badge-green"><i class="fa-solid fa-check"></i> Complete</span>'; break;
                case 'pending':   echo '<span class="badge badge-yellow">Pending</span>'; break;
                case 'failed':    echo '<span class="badge badge-red">Failed</span>'; break;
                case 'cancelled': echo '<span class="badge badge-gray">Cancelled</span>'; break;
                default:          echo h($d['status']);
              }
            ?></td>
            <td class="text-muted text-sm"><?= date('j M Y H:i', strtotime($d['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($result['data'])): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No payments yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($result['pages'] > 1): ?>
<div class="pagination">
  <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
    <a href="?page=<?= $p ?>" class="page-btn <?= $p == $result['page'] ? 'active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow mb-3 text-center py-3"
     style="background:var(--c-dark);color:#fff;border-radius:16px">
  <div style="font-size:.8rem;opacity:.7">발급된 급여명세서</div>
  <div style="font-size:1.05rem;font-weight:700"><?= h($member['name'] ?? '') ?></div>
</div>

<?php if (empty($payslips)): ?>
<div class="alert alert-info text-center" style="border-radius:12px">
  <i class="bi bi-inbox me-1"></i>발급된 급여명세서가 없습니다.
</div>
<?php else: ?>
<div class="d-flex flex-column gap-2 mb-4">
  <?php foreach ($payslips as $p): ?>
  <a href="<?= url('employee', 'payslip_show', ['id' => (int)$p['id']]) ?>"
     class="card border-0 shadow-sm text-decoration-none text-dark">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
      <div>
        <div class="fw-semibold"><?= h($p['period_start']) ?> ~ <?= h($p['period_end']) ?></div>
        <div class="small text-muted">
          발급 <?= h($p['issued_at'] ? substr($p['issued_at'], 0, 10) : '-') ?>
          <?php if (!empty($p['corrected_from_payslip_id'])): ?>
          · <span class="badge bg-warning text-dark">정정본</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="text-end">
        <div class="fw-bold" style="color:var(--c-teal)"><?= formatWon($p['net_pay']) ?></div>
        <div class="small text-muted"><i class="bi bi-chevron-right"></i></div>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<a href="<?= url('employee') ?>" class="btn btn-outline-secondary w-100 mb-4">
  <i class="bi bi-arrow-left me-1"></i>돌아가기
</a>

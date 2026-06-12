<?php
$plans    = ['FREE','STARTER','BUSINESS','PRO'];
$statuses = ['FREE','TRIAL','PAYMENT_PENDING','PAID','FAILED','CANCEL_SCHEDULED','CANCELLED'];
?>
<div class="admin-card mb-3">
  <div class="admin-card-header"><span><i class="bi bi-plus-circle me-1"></i>구독 수동 변경</span></div>
  <div class="admin-card-body">
    <form method="POST" action="<?= url('admin', 'billing_update') ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-auto">
        <label class="form-label small mb-1">사업장 ID</label>
        <input type="number" name="store_id" class="form-control form-control-sm" required style="width:120px">
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">요금제</label>
        <select name="plan" class="form-select form-select-sm">
          <?php foreach ($plans as $pl): ?><option value="<?= h($pl) ?>"><?= adminLabel($pl) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">상태</label>
        <select name="status" class="form-select form-select-sm">
          <?php foreach ($statuses as $st): ?><option value="<?= h($st) ?>"><?= adminLabel($st) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">사유</label>
        <input type="text" name="reason" class="form-control form-control-sm" placeholder="변경 사유">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-accent);color:#fff">저장</button>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header"><span><i class="bi bi-credit-card-fill me-1"></i>구독 목록</span><span class="small text-muted"><?= count($subs) ?>건</span></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>사업장</th><th>대표</th><th>요금제</th><th>상태</th><th class="text-end">월 금액</th><th>무료체험 종료</th><th>다음 결제일</th><th>수정일</th></tr></thead>
      <tbody>
        <?php if (empty($subs)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">구독 정보가 없습니다.</td></tr>
        <?php else: foreach ($subs as $sub): ?>
          <tr>
            <td class="fw-semibold"><?= h($sub['store_name'] ?? ('#' . (int)$sub['store_id'])) ?></td>
            <td class="small"><?= h($sub['owner_name'] ?? '-') ?></td>
            <td><?= adminBadge($sub['plan'] ?? 'FREE') ?></td>
            <td><?= adminBadge($sub['status'] ?? 'FREE') ?></td>
            <td class="text-end small"><?= number_format((int)($sub['monthly_amount'] ?? 0)) ?>원</td>
            <td class="small text-muted"><?= h($sub['trial_ends_at'] ?? '-') ?></td>
            <td class="small text-muted"><?= h($sub['next_billing_date'] ?? '-') ?></td>
            <td class="small text-muted"><?= h(substr((string)($sub['updated_at'] ?? ''), 0, 16)) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

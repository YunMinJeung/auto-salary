<?php $statuses = ['ACTIVE','TRIAL','PAYMENT_PENDING','SUSPENDED','CANCEL_REQUESTED','INACTIVE']; ?>
<div class="admin-card mb-3">
  <div class="admin-card-body">
    <form method="GET" action="<?= url('admin', 'businesses') ?>" class="row g-2 align-items-end">
      <input type="hidden" name="c" value="admin">
      <input type="hidden" name="a" value="businesses">
      <div class="col-auto">
        <label class="form-label small mb-1">상태</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">전체</option>
          <?php foreach ($statuses as $st): ?>
            <option value="<?= h($st) ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= adminLabel($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">검색 (사업장명/이메일)</label>
        <input type="text" name="search" class="form-control form-control-sm" value="<?= h($filters['search'] ?? '') ?>" style="min-width:240px">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-accent);color:#fff"><i class="bi bi-search me-1"></i>검색</button>
        <a href="<?= url('admin', 'businesses') ?>" class="btn btn-sm btn-outline-secondary">초기화</a>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <span><i class="bi bi-building me-1"></i>사업장 목록</span>
    <span class="small text-muted"><?= count($stores) ?>건</span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>사업장명</th><th>대표 사장</th><th>가입일</th><th>상태</th><th>요금제</th>
          <th class="text-end">직원수</th><th class="text-end">이달 근무기록</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($stores)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">조건에 맞는 사업장이 없습니다.</td></tr>
        <?php else: foreach ($stores as $s): ?>
          <tr>
            <td class="fw-semibold"><?= h($s['store_name']) ?></td>
            <td><?= h($s['owner_name'] ?? '-') ?><br><span class="small text-muted"><?= h($s['owner_email'] ?? '') ?></span></td>
            <td class="small text-muted"><?= h(substr((string)($s['created_at'] ?? ''), 0, 10)) ?></td>
            <td><?= adminBadge($s['status'] ?? 'ACTIVE') ?></td>
            <td><?= adminBadge($s['plan'] ?? 'FREE') ?></td>
            <td class="text-end"><?= number_format((int)($s['member_count'] ?? 0)) ?></td>
            <td class="text-end"><?= number_format((int)($s['monthly_log_count'] ?? 0)) ?></td>
            <td class="text-end">
              <a href="<?= url('admin', 'business_detail', ['id' => $s['id']]) ?>" class="btn btn-sm btn-outline-secondary">상세</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

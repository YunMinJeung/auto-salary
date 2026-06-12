<?php
$statusLabels = Payslip::STATUS_LABELS;
$statusBadges = Payslip::STATUS_BADGES;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-file-earmark-text me-2 text-success"></i>급여명세서 관리</h1>
  <a href="<?= url('payroll') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-cash-coin me-1"></i>급여 계산으로
  </a>
</div>

<!-- 필터 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" action="<?= url('payslip') ?>" class="row g-3 align-items-end">
      <input type="hidden" name="c" value="payslip">
      <div class="col-6 col-md-3">
        <label class="form-label fw-semibold small">직원</label>
        <select name="employee_id" class="form-select form-select-sm">
          <option value="0">전체 직원</option>
          <?php foreach ($employees as $emp): ?>
          <option value="<?= (int)$emp['id'] ?>" <?= (int)$filters['employee_id'] === (int)$emp['id'] ? 'selected' : '' ?>>
            <?= h($emp['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label fw-semibold small">상태</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">전체</option>
          <?php foreach ($statusLabels as $code => $label): ?>
          <option value="<?= h($code) ?>" <?= $filters['status'] === $code ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label fw-semibold small">연도</label>
        <input type="number" name="year" class="form-control form-control-sm" value="<?= $filters['year'] ?: '' ?>" placeholder="<?= date('Y') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label fw-semibold small">월</label>
        <input type="number" name="month" min="1" max="12" class="form-control form-control-sm" value="<?= $filters['month'] ?: '' ?>" placeholder="월">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>조회</button>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th class="ps-3">기간</th>
          <th>직원명</th>
          <th class="text-center">버전</th>
          <th class="text-center">상태</th>
          <th class="text-end">세전</th>
          <th class="text-end">실지급</th>
          <th>발급일</th>
          <th class="pe-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payslips)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">
          <i class="bi bi-inbox fs-3 d-block mb-2"></i>발급된 급여명세서가 없습니다.
        </td></tr>
        <?php else: foreach ($payslips as $p): ?>
        <tr>
          <td class="ps-3 text-nowrap"><?= h($p['period_start']) ?> ~ <?= h($p['period_end']) ?></td>
          <td><?= h($p['employee_name'] ?? '-') ?></td>
          <td class="text-center">v<?= (int)$p['version'] ?></td>
          <td class="text-center">
            <span class="badge <?= $statusBadges[$p['status']] ?? 'bg-secondary' ?>">
              <?= h($statusLabels[$p['status']] ?? $p['status']) ?>
            </span>
          </td>
          <td class="text-end"><?= formatWon($p['gross_pay']) ?></td>
          <td class="text-end fw-semibold"><?= formatWon($p['net_pay']) ?></td>
          <td class="text-nowrap"><?= h($p['issued_at'] ? substr($p['issued_at'], 0, 10) : '—') ?></td>
          <td class="pe-3 text-end">
            <a href="<?= url('payslip', 'show', ['id' => (int)$p['id']]) ?>" class="btn btn-xs btn-outline-primary py-0 px-2">
              <i class="bi bi-eye"></i> 보기
            </a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

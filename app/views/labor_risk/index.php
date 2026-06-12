<?php
$scopeLabels = [
    'employee_visible_required'          => '직원공개필수',
    'owner_only'                         => '사장전용',
    'owner_review_then_employee_visible' => '검토후공개',
];
$statusLabels = [
    'open'         => ['열림',   'bg-secondary'],
    'acknowledged' => ['확인함', 'bg-primary'],
    'resolved'     => ['해결됨', 'bg-success'],
    'ignored'      => ['무시함', 'bg-light text-dark border'],
];
$sevBadge = [
    'danger'  => ['위험', 'bg-danger'],
    'warning' => ['주의', 'bg-warning text-dark'],
    'info'    => ['참고', 'bg-info text-dark'],
];
?>

<h4 class="fw-bold mb-3" style="color:var(--c-dark)">
  <i class="bi bi-shield-exclamation me-2" style="color:#dc3545"></i>노무 리스크 알림
</h4>

<form method="post" action="<?= url('labor_risk', 'scan') ?>" class="mb-3">
  <?= csrf_field() ?>
  <button type="submit" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-arrow-clockwise me-1"></i>전체 리스크 스캔
  </button>
  <span class="text-muted small ms-2">모든 직원·최근 90일 근무기록을 재검사합니다</span>
</form>

<!-- 요약 카드 -->
<div class="row g-3 mb-4">
  <div class="col-4">
    <div class="card border-0 h-100" style="border-left:4px solid #dc3545 !important;">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-2 text-danger"><?= (int) $counts['danger'] ?></div>
        <div class="small text-muted">위험</div>
      </div>
    </div>
  </div>
  <div class="col-4">
    <div class="card border-0 h-100" style="border-left:4px solid #FFB100 !important;">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-2" style="color:#FFB100"><?= (int) $counts['warning'] ?></div>
        <div class="small text-muted">주의</div>
      </div>
    </div>
  </div>
  <div class="col-4">
    <div class="card border-0 h-100" style="border-left:4px solid #0dcaf0 !important;">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-2" style="color:#0dcaf0"><?= (int) $counts['info'] ?></div>
        <div class="small text-muted">참고</div>
      </div>
    </div>
  </div>
</div>

<!-- 필터 -->
<form method="get" action="<?= url('labor_risk') ?>" class="row g-2 align-items-end mb-3">
  <div class="col-auto">
    <label class="form-label small mb-1">등급</label>
    <select name="severity" class="form-select form-select-sm">
      <option value="">전체</option>
      <option value="danger"  <?= ($filters['severity'] ?? '') === 'danger'  ? 'selected' : '' ?>>위험</option>
      <option value="warning" <?= ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' ?>>주의</option>
      <option value="info"    <?= ($filters['severity'] ?? '') === 'info'    ? 'selected' : '' ?>>참고</option>
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label small mb-1">상태</label>
    <select name="status" class="form-select form-select-sm">
      <option value="active" <?= ($filters['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>열린 것</option>
      <option value="all"    <?= ($filters['status'] ?? '') === 'all'          ? 'selected' : '' ?>>전체</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-funnel me-1"></i>필터
    </button>
  </div>
</form>

<!-- 알림 목록 -->
<?php if (empty($alerts)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center text-muted py-5">
    <i class="bi bi-check-circle d-block mb-2" style="font-size:2rem; color:var(--c-teal)"></i>
    표시할 노무 리스크 알림이 없습니다.
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="list-group list-group-flush">
    <?php foreach ($alerts as $alert): ?>
      <?php
        $sev   = $alert['severity'];
        $sb    = $sevBadge[$sev] ?? ['?', 'bg-secondary'];
        $st    = $statusLabels[$alert['status']] ?? [$alert['status'], 'bg-secondary'];
        $scope = $scopeLabels[$alert['visibility_scope']] ?? $alert['visibility_scope'];
        $lockIgnore = $alert['visibility_scope'] === 'employee_visible_required';
      ?>
      <div class="list-group-item px-3 py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div class="flex-grow-1">
            <div class="mb-1">
              <span class="badge <?= $sb[1] ?> me-1"><?= $sb[0] ?></span>
              <span class="fw-semibold" style="color:var(--c-dark)"><?= h($alert['title']) ?></span>
              <span class="badge <?= $st[1] ?> ms-1"><?= $st[0] ?></span>
            </div>
            <div class="small text-muted mb-1"><?= nl2br(h($alert['message'])) ?></div>
            <div class="text-muted" style="font-size:.78rem;">
              <?php if (!empty($alert['employee_name'])): ?>
                <i class="bi bi-person me-1"></i><?= h($alert['employee_name']) ?>
              <?php endif; ?>
              <i class="bi bi-clock ms-2 me-1"></i><?= h($alert['created_at']) ?>
              <span class="badge bg-light text-dark border ms-2"><?= h($scope) ?></span>
              <?php if (!empty($alert['legal_basis'])): ?>
                <span class="ms-2"><i class="bi bi-journal-text me-1"></i><?= h($alert['legal_basis']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex gap-1 flex-shrink-0">
            <form method="post" action="<?= url('labor_risk', 'acknowledge') ?>" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-primary" title="확인함">
                <i class="bi bi-eye"></i>
              </button>
            </form>
            <form method="post" action="<?= url('labor_risk', 'resolve') ?>" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-success" title="해결됨">
                <i class="bi bi-check2-circle"></i>
              </button>
            </form>
            <form method="post" action="<?= url('labor_risk', 'ignore') ?>" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary" title="무시함"
                      <?= $lockIgnore ? 'disabled' : '' ?>
                      <?= $lockIgnore ? 'data-bs-toggle="tooltip" title="직원공개필수 항목은 무시할 수 없습니다"' : '' ?>>
                <i class="bi bi-slash-circle"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- 면책 문구 -->
<div class="alert alert-light border small text-muted mt-4">
  <i class="bi bi-info-circle me-1"></i>
  본 알림은 입력된 근무기록과 설정값을 기준으로 한 참고용 안내입니다.
  실제 법적 판단은 근로계약서, 실제 근무형태, 사업장 규모, 최신 법령 및 행정해석에 따라 달라질 수 있습니다.
  필요 시 고용노동부, 근로복지공단, 국민연금공단, 국민건강보험공단 또는 노무사에게 확인하세요.
</div>

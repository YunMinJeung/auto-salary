<?php
$settings = Setting::get();
$today    = date('Y-m-d');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-house-fill me-2 text-primary"></i>대시보드</h1>
  <span class="text-muted small"><?= h($settings['business_name']) ?></span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card text-center border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="fs-2 text-primary fw-bold"><?= count($employees) ?></div>
        <div class="text-muted small">재직 중 직원</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="fs-2 text-success fw-bold"><?= number_format($settings['minimum_wage']) ?>원</div>
        <div class="text-muted small"><?= $settings['minimum_wage_year'] ?>년 최저시급</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="fs-5 fw-semibold text-dark">
          <?= $settings['employee_count_type'] === 'over5' ? '5인 이상' : '5인 미만' ?>
        </div>
        <div class="text-muted small">사업장 규모</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="fs-5 fw-semibold text-dark"><?= count($recentLogs) ?>건</div>
        <div class="text-muted small">최근 근무 기록</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-people-fill text-primary me-1"></i>재직 중 직원
      </div>
      <div class="card-body p-0">
        <?php if (empty($employees)): ?>
        <p class="p-3 text-muted mb-0">등록된 직원이 없습니다.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($employees as $emp): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
              <i class="bi bi-person-circle me-1 text-muted"></i>
              <a href="<?= url('work_logs', 'index', ['employee_id' => $emp['id']]) ?>"
                 class="text-decoration-none"><?= h($emp['name']) ?></a>
            </span>
            <span class="badge bg-light text-dark border">
              <?= number_format($emp['hourly_wage']) ?>원/시
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-white">
        <a href="<?= url('employees', 'create') ?>" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-circle me-1"></i>직원 등록
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history text-success me-1"></i>최근 근무 기록
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentLogs)): ?>
        <p class="p-3 text-muted mb-0">근무 기록이 없습니다.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($recentLogs, 0, 7) as $log): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <span class="small">
              <span class="fw-semibold"><?= h($log['employee_name']) ?></span>
              <span class="text-muted ms-2"><?= h($log['work_date']) ?>(<?= dayOfWeekKo($log['work_date']) ?>)</span>
            </span>
            <span class="text-muted small">
              <?php if ($log['is_absent']): ?>
                <span class="badge bg-danger">결근</span>
              <?php else: ?>
                <?= h(substr($log['start_time'], 0, 5)) ?>~<?= h(substr($log['end_time'], 0, 5)) ?>
              <?php endif; ?>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-white">
        <a href="<?= url('work_logs', 'create') ?>" class="btn btn-sm btn-success">
          <i class="bi bi-plus-circle me-1"></i>근무 기록 추가
        </a>
        <a href="<?= url('payroll') ?>" class="btn btn-sm btn-outline-primary ms-1">
          <i class="bi bi-calculator me-1"></i>급여 계산
        </a>
      </div>
    </div>
  </div>
</div>

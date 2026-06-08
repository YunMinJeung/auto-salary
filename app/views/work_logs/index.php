<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-calendar3 me-2 text-success"></i>근무 기록</h1>
  <a href="<?= url('work_logs', 'create', $employeeId ? ['employee_id' => $employeeId] : []) ?>"
     class="btn btn-success">
    <i class="bi bi-plus-circle me-1"></i>근무 기록 추가
  </a>
</div>

<!-- 직원 필터 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body py-2">
    <form method="get" action="<?= url('work_logs') ?>" class="d-flex align-items-center gap-2 flex-wrap">
      <input type="hidden" name="c" value="work_logs">
      <label class="fw-semibold me-1 mb-0">직원 필터:</label>
      <select name="employee_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        <option value="">— 전체 —</option>
        <?php foreach ($employees as $emp): ?>
        <option value="<?= $emp['id'] ?>" <?= $employeeId === $emp['id'] ? 'selected' : '' ?>>
          <?= h($emp['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php if ($employeeId): ?>
        <a href="<?= url('payroll', 'index', ['employee_id' => $employeeId, 'week_date' => date('Y-m-d')]) ?>"
           class="btn btn-sm btn-outline-primary">
          <i class="bi bi-calculator me-1"></i>급여 계산
        </a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if (empty($logs)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>근무 기록이 없습니다.
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>날짜</th>
          <?php if (!$employeeId): ?><th>직원</th><?php endif; ?>
          <th>시작</th>
          <th>마감</th>
          <th>근무</th>
          <th>휴게</th>
          <th>유급</th>
          <th>구분</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <?php
          $calc = new PayrollCalculator();
          if (!$log['is_absent']) {
              $wm = $calc->calculateWorkMinutes($log['work_date'], $log['start_time'], $log['end_time']);
              $bm = $log['break_auto'] ? $calc->calculateAutoBreakMinutes($wm) : (int)$log['break_minutes'];
              $pm = $calc->calculatePaidWorkMinutes($wm, $bm);
          }
        ?>
        <tr>
          <td class="fw-semibold">
            <?= h($log['work_date']) ?>
            <span class="text-muted">(<?= dayOfWeekKo($log['work_date']) ?>)</span>
          </td>
          <?php if (!$employeeId): ?>
          <td><?= h($log['employee_name']) ?></td>
          <?php endif; ?>

          <?php if ($log['is_absent']): ?>
          <td colspan="5" class="text-danger">
            <i class="bi bi-x-circle me-1"></i>결근
          </td>
          <?php else: ?>
          <td><?= h(substr($log['start_time'], 0, 5)) ?></td>
          <td><?= h(substr($log['end_time'], 0, 5)) ?></td>
          <td><?= minutesToHoursStr($wm) ?></td>
          <td class="text-muted">
            <?= minutesToHoursStr($bm) ?>
            <?= $log['break_auto'] ? '<span class="badge bg-light text-muted border">자동</span>' : '' ?>
          </td>
          <td class="fw-semibold"><?= minutesToHoursStr($pm) ?></td>
          <?php endif; ?>

          <td>
            <?php if ($log['is_holiday']): ?>
              <span class="badge bg-warning-subtle text-warning border">휴일</span>
            <?php endif; ?>
            <?php if ($log['is_late']): ?>
              <span class="badge bg-info-subtle text-info border">지각</span>
            <?php endif; ?>
            <?php if ($log['is_early_leave']): ?>
              <span class="badge bg-info-subtle text-info border">조퇴</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('work_logs', 'edit', ['id' => $log['id']]) ?>"
                 class="btn btn-xs btn-outline-primary py-0 px-1">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" action="<?= url('work_logs', 'delete') ?>"
                    onsubmit="return confirm('이 근무 기록을 삭제하시겠습니까?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $log['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

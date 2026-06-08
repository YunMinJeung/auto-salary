<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>직원 관리</h1>
  <a href="<?= url('employees', 'create') ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>직원 등록
  </a>
</div>

<?php if (empty($employees)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
    등록된 직원이 없습니다.
    <a href="<?= url('employees', 'create') ?>" class="d-block mt-2">첫 직원 등록하기</a>
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>이름</th>
          <th>시급</th>
          <th>주 소정근로시간</th>
          <th>주휴수당</th>
          <th>입사일</th>
          <th>상태</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $emp): ?>
        <tr>
          <td class="fw-semibold"><?= h($emp['name']) ?></td>
          <td><?= number_format($emp['hourly_wage']) ?>원</td>
          <td><?= h($emp['weekly_scheduled_hours']) ?>시간 / <?= h($emp['weekly_scheduled_days']) ?>일</td>
          <td>
            <?php if ($emp['weekly_holiday_enabled']): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">대상</span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border">제외</span>
            <?php endif; ?>
          </td>
          <td class="text-muted small"><?= h($emp['employment_start_date']) ?></td>
          <td>
            <?php if ($emp['employment_end_date']): ?>
              <span class="badge bg-danger-subtle text-danger border">퇴직</span>
            <?php else: ?>
              <span class="badge bg-primary-subtle text-primary border">재직</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('work_logs', 'index', ['employee_id' => $emp['id']]) ?>"
                 class="btn btn-sm btn-outline-secondary" title="근무 기록">
                <i class="bi bi-calendar3"></i>
              </a>
              <a href="<?= url('payroll', 'index', ['employee_id' => $emp['id'], 'week_date' => date('Y-m-d')]) ?>"
                 class="btn btn-sm btn-outline-success" title="급여 계산">
                <i class="bi bi-calculator"></i>
              </a>
              <a href="<?= url('employees', 'edit', ['id' => $emp['id']]) ?>"
                 class="btn btn-sm btn-outline-primary" title="수정">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" action="<?= url('employees', 'delete') ?>"
                    onsubmit="return confirm('<?= h($emp['name']) ?>님을 삭제하면 모든 근무 기록도 삭제됩니다. 계속하시겠습니까?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="삭제">
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

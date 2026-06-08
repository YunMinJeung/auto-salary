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

<!-- 검색 / 필터 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2 px-3">
    <div class="d-flex flex-wrap gap-3 align-items-center">
      <div class="input-group" style="max-width:260px">
        <span class="input-group-text bg-white border-end-0">
          <i class="bi bi-search text-muted"></i>
        </span>
        <input type="search" id="emp-search" class="form-control border-start-0 ps-0"
               placeholder="이름 검색" oninput="filterTable()">
      </div>
      <div class="d-flex gap-2">
        <div class="form-check form-check-inline mb-0">
          <input class="form-check-input" type="radio" name="emp-status" id="st-all" value="all" checked onchange="filterTable()">
          <label class="form-check-label" for="st-all">전체</label>
        </div>
        <div class="form-check form-check-inline mb-0">
          <input class="form-check-input" type="radio" name="emp-status" id="st-active" value="active" onchange="filterTable()">
          <label class="form-check-label" for="st-active">재직</label>
        </div>
        <div class="form-check form-check-inline mb-0">
          <input class="form-check-input" type="radio" name="emp-status" id="st-retired" value="retired" onchange="filterTable()">
          <label class="form-check-label" for="st-retired">퇴직</label>
        </div>
      </div>
      <span class="text-muted small ms-auto" id="emp-count"></span>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="emp-table">
      <thead class="table-light">
        <tr>
          <th>이름</th>
          <th>시급</th>
          <th>소정근로</th>
          <th>주휴수당 대상</th>
          <th>입사일</th>
          <th>상태</th>
          <th class="text-end pe-3">액션</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $emp):
          $isRetired = !empty($emp['employment_end_date']);
          // 소수점 없는 시간 표기: 32.0→32, 37.5→37.5
          $hrs = (float)$emp['weekly_scheduled_hours'];
          $hrsStr = ($hrs == floor($hrs)) ? (int)$hrs : $hrs;
        ?>
        <tr data-name="<?= h(mb_strtolower($emp['name'])) ?>"
            data-retired="<?= $isRetired ? '1' : '0' ?>">

          <!-- 이름: 클릭 시 수정 페이지 -->
          <td>
            <a href="<?= url('employees', 'edit', ['id' => $emp['id']]) ?>"
               class="fw-semibold text-decoration-none" style="color:var(--c-dark)">
              <?= h($emp['name']) ?>
            </a>
          </td>

          <td><?= number_format($emp['hourly_wage']) ?>원</td>

          <td class="text-muted small">
            <?= $hrsStr ?>시간 / <?= (int)$emp['weekly_scheduled_days'] ?>일
          </td>

          <td>
            <?php if ($emp['weekly_holiday_enabled']): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">대상</span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border">제외</span>
            <?php endif; ?>
          </td>

          <td class="text-muted small"><?= h($emp['employment_start_date']) ?></td>

          <td>
            <?php if ($isRetired): ?>
              <span class="badge bg-danger-subtle text-danger border" title="퇴직일: <?= h($emp['employment_end_date']) ?>">퇴직</span>
            <?php else: ?>
              <span class="badge bg-primary-subtle text-primary border">재직</span>
            <?php endif; ?>
          </td>

          <!-- 액션 버튼 -->
          <td class="text-end pe-3">
            <div class="d-flex gap-1 justify-content-end">
              <a href="<?= url('work_logs', 'index', ['employee_id' => $emp['id']]) ?>"
                 class="btn btn-sm btn-outline-secondary"
                 data-bs-toggle="tooltip" data-bs-placement="top" title="근무 기록">
                <i class="bi bi-calendar3"></i>
              </a>
              <a href="<?= url('payroll', 'index', ['employee_id' => $emp['id'], 'week_date' => date('Y-m-d')]) ?>"
                 class="btn btn-sm btn-outline-success"
                 data-bs-toggle="tooltip" data-bs-placement="top" title="주간 급여 계산">
                <i class="bi bi-calculator"></i>
              </a>
              <a href="<?= url('severance', 'index', ['employee_id' => $emp['id']]) ?>"
                 class="btn btn-sm btn-outline-warning"
                 data-bs-toggle="tooltip" data-bs-placement="top" title="퇴직금 계산">
                <i class="bi bi-bank"></i>
              </a>
              <a href="<?= url('employees', 'edit', ['id' => $emp['id']]) ?>"
                 class="btn btn-sm btn-outline-primary"
                 data-bs-toggle="tooltip" data-bs-placement="top" title="직원 정보 수정">
                <i class="bi bi-pencil"></i>
              </a>
              <!-- 삭제: 시각적으로 분리 -->
              <form method="post" action="<?= url('employees', 'delete') ?>"
                    onsubmit="return confirmDelete('<?= h(addslashes($emp['name'])) ?>')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger ms-2"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="직원 삭제">
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

<script>
function confirmDelete(name) {
  return confirm(name + ' 직원을 삭제하면 모든 근무 기록도 함께 삭제됩니다.\n\n정말 삭제하시겠습니까?');
}

function filterTable() {
  var q       = document.getElementById('emp-search').value.toLowerCase();
  var status  = document.querySelector('[name=emp-status]:checked').value;
  var rows    = document.querySelectorAll('#emp-table tbody tr');
  var visible = 0;
  rows.forEach(function(row) {
    var name    = row.dataset.name || '';
    var retired = row.dataset.retired === '1';
    var matchQ  = name.includes(q);
    var matchS  = status === 'all'
               || (status === 'active'  && !retired)
               || (status === 'retired' && retired);
    var show    = matchQ && matchS;
    row.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  var countEl = document.getElementById('emp-count');
  if (countEl) countEl.textContent = visible + '명';
}

// 초기 카운트 표시
document.addEventListener('DOMContentLoaded', filterTable);
</script>

<?php endif; ?>

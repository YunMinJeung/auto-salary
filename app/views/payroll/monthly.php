<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>월간 급여 요약</h1>
</div>

<!-- 조회 폼 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" action="<?= url('payroll', 'monthly') ?>" class="row g-3 align-items-end">
      <input type="hidden" name="c" value="payroll">
      <input type="hidden" name="a" value="monthly">
      <div class="col-12 col-sm-4">
        <label class="form-label fw-semibold">직원</label>
        <select name="employee_id" class="form-select">
          <option value="">— 선택 —</option>
          <?php foreach ($employees as $emp): ?>
          <option value="<?= $emp['id'] ?>" <?= $employeeId === $emp['id'] ? 'selected' : '' ?>>
            <?= h($emp['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-sm-2">
        <label class="form-label fw-semibold">연도</label>
        <input type="number" name="year" class="form-control" value="<?= h($year) ?>" min="2020" max="2100">
      </div>
      <div class="col-6 col-sm-2">
        <label class="form-label fw-semibold">월</label>
        <select name="month" class="form-select">
          <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $m ?>월</option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-search me-1"></i>조회
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($data): ?>
<?php $emp = $data['employee']; $m = $data['monthly']; ?>

<!-- 월간 합계 카드 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-primary text-white d-flex justify-content-between">
    <span class="fw-bold">
      <i class="bi bi-person-fill me-1"></i><?= h($emp['name']) ?>
      · <?= h($year) ?>년 <?= h($month) ?>월
    </span>
    <span class="badge bg-white text-primary fs-6">
      총 <?= formatWon($m['total_pay']) ?>
    </span>
  </div>
  <div class="card-body">
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-4">
        <div class="border rounded p-3 text-center">
          <div class="text-muted small">유급 근무시간</div>
          <div class="fw-bold fs-5"><?= minutesToHoursStr($m['paid_work_minutes']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="border rounded p-3 text-center">
          <div class="text-muted small">기본급</div>
          <div class="fw-bold fs-5"><?= formatWon($m['base_pay']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="border rounded p-3 text-center">
          <div class="text-muted small">주휴수당 합계</div>
          <div class="fw-bold fs-5 text-success"><?= formatWon($m['weekly_holiday_pay']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="border rounded p-3 text-center">
          <div class="text-muted small">야간 가산수당</div>
          <div class="fw-bold"><?= formatWon($m['night_premium']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="border rounded p-3 text-center">
          <div class="text-muted small">연장 가산수당</div>
          <div class="fw-bold"><?= formatWon($m['overtime_premium']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="border rounded p-3 text-center bg-success-subtle">
          <div class="text-muted small">총 지급 예상액</div>
          <div class="fw-bold fs-5 text-success"><?= formatWon($m['total_pay']) ?></div>
        </div>
      </div>
    </div>

    <a href="<?= url('payroll', 'export_csv', ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]) ?>"
       class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-download me-1"></i>CSV 다운로드
    </a>
  </div>
</div>

<!-- 주별 상세 -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-list-ul me-1"></i>주별 내역
  </div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>기간</th>
          <th class="text-end">유급근무</th>
          <th class="text-end">야간</th>
          <th class="text-end">연장</th>
          <th class="text-end">기본급</th>
          <th class="text-end">주휴수당</th>
          <th class="text-end">가산수당</th>
          <th class="text-end fw-bold">소계</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['weeks'] as $w): ?>
        <tr>
          <td class="text-nowrap">
            <?= h($w['period_start']) ?> ~ <?= h($w['period_end']) ?>
          </td>
          <td class="text-end"><?= minutesToHoursStr($w['paid_work_minutes']) ?></td>
          <td class="text-end text-primary"><?= $w['night_minutes'] > 0 ? minutesToHoursStr($w['night_minutes']) : '—' ?></td>
          <td class="text-end"><?= $w['overtime_minutes'] > 0 ? minutesToHoursStr($w['overtime_minutes']) : '—' ?></td>
          <td class="text-end"><?= formatWon($w['base_pay']) ?></td>
          <td class="text-end text-success"><?= $w['weekly_holiday_pay'] > 0 ? formatWon($w['weekly_holiday_pay']) : '—' ?></td>
          <td class="text-end"><?= formatWon($w['night_premium'] + $w['overtime_premium'] + $w['holiday_premium']) ?></td>
          <td class="text-end fw-bold"><?= formatWon($w['total_pay']) ?></td>
          <td>
            <a href="<?= url('payroll', 'index', ['employee_id' => $employeeId, 'week_date' => $w['period_start']]) ?>"
               class="btn btn-xs btn-outline-primary py-0 px-1" title="주간 상세">
              <i class="bi bi-search"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="table-success fw-bold">
          <td>합 계</td>
          <td class="text-end"><?= minutesToHoursStr($m['paid_work_minutes']) ?></td>
          <td class="text-end"><?= minutesToHoursStr($m['night_minutes']) ?></td>
          <td class="text-end"><?= minutesToHoursStr($m['overtime_minutes']) ?></td>
          <td class="text-end"><?= formatWon($m['base_pay']) ?></td>
          <td class="text-end"><?= formatWon($m['weekly_holiday_pay']) ?></td>
          <td class="text-end"><?= formatWon($m['night_premium'] + $m['overtime_premium'] + $m['holiday_premium']) ?></td>
          <td class="text-end"><?= formatWon($m['total_pay']) ?></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="alert alert-warning mt-4 small">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  본 계산 결과는 참고용 예상 금액입니다.
  실제 임금 지급 전 근로계약서·최신 법령·전문가 확인을 권장합니다.
</div>

<?php elseif ($employeeId): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-1"></i>
  <?= h($year) ?>년 <?= h($month) ?>월에 해당하는 근무 기록이 없습니다.
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>월간 급여 요약</h1>
  <?php if ($employeeId > 0): ?>
  <a href="<?= url('payroll', 'monthly', ['year' => $year, 'month' => $month]) ?>"
     class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-list-ul me-1"></i>전체 보기
  </a>
  <?php endif; ?>
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
          <option value="">전체 직원</option>
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
          <?php for ($mo = 1; $mo <= 12; $mo++): ?>
          <option value="<?= $mo ?>" <?= $mo === $month ? 'selected' : '' ?>><?= $mo ?>월</option>
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

<?php if ($allData !== null): ?>
<?php
// ══ 전체 직원 요약 뷰 ══════════════════════════════════════
$totalBase = 0; $totalHol = 0; $totalPrem = 0; $totalSum = 0; $totalPaid = 0;
$periodMinWage = MinimumWage::effectiveHourlyWage("{$year}-{$month}-01");
?>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
    <span>
      <i class="bi bi-people-fill me-1 text-primary"></i>
      <?= h($year) ?>년 <?= h($month) ?>월
      <span class="text-muted small ms-2">(<?= count($allData) ?>명)</span>
    </span>
    <span class="text-muted small">
      <?= h($year) ?>년 최저시급: <?= number_format($periodMinWage) ?>원
    </span>
  </div>

  <?php if (empty($allData)): ?>
  <div class="card-body text-center py-4 text-muted">
    <i class="bi bi-people fs-3 d-block mb-2"></i>
    등록된 직원이 없습니다.
    <a href="<?= url('employees', 'create') ?>" class="d-block mt-2">직원 등록하기</a>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th class="ps-3">이름</th>
          <th class="text-end">시급</th>
          <th class="text-end">유급근무</th>
          <th class="text-end">야간</th>
          <th class="text-end">연장</th>
          <th class="text-end">기본급</th>
          <th class="text-end">주휴수당</th>
          <th class="text-end">가산수당</th>
          <th class="text-end fw-bold">합계</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allData as $d):
          $emp   = $d['employee'];
          $mo    = $d['monthly'];
          $prem  = $mo['night_premium'] + $mo['overtime_premium'] + $mo['holiday_premium'];
          $hasLogs = $mo['total_pay'] > 0 || $mo['paid_work_minutes'] > 0;
          $belowMin = $emp['hourly_wage'] < $periodMinWage;
          if ($hasLogs) {
              $totalPaid += $mo['paid_work_minutes'];
              $totalBase += $mo['base_pay'];
              $totalHol  += $mo['weekly_holiday_pay'];
              $totalPrem += $prem;
              $totalSum  += $mo['total_pay'];
          }
        ?>
        <tr class="<?= !$hasLogs ? 'text-muted' : '' ?>">
          <td class="ps-3">
            <?php if ($belowMin && $hasLogs): ?>
            <i class="bi bi-exclamation-triangle-fill text-danger me-1"
               data-bs-toggle="tooltip"
               title="시급(<?= number_format($emp['hourly_wage']) ?>원)이 최저시급보다 낮습니다"></i>
            <?php endif; ?>
            <span class="<?= $hasLogs ? 'fw-semibold' : '' ?>">
              <?= h($emp['name']) ?>
            </span>
          </td>
          <td class="text-end"><?= number_format($emp['hourly_wage']) ?>원</td>
          <?php if ($hasLogs): ?>
          <td class="text-end"><?= minutesToHoursStr($mo['paid_work_minutes']) ?></td>
          <td class="text-end text-primary"><?= $mo['night_minutes']    > 0 ? minutesToHoursStr($mo['night_minutes'])    : '—' ?></td>
          <td class="text-end"><?=             $mo['overtime_minutes']  > 0 ? minutesToHoursStr($mo['overtime_minutes'])  : '—' ?></td>
          <td class="text-end"><?= formatWon($mo['base_pay']) ?></td>
          <td class="text-end text-success"><?= $mo['weekly_holiday_pay'] > 0 ? formatWon($mo['weekly_holiday_pay']) : '—' ?></td>
          <td class="text-end"><?= $prem > 0 ? formatWon($prem) : '—' ?></td>
          <td class="text-end fw-bold"><?= formatWon($mo['total_pay']) ?></td>
          <?php else: ?>
          <td colspan="7" class="text-center text-muted small fst-italic">이달 근무 기록 없음</td>
          <?php endif; ?>
          <td class="pe-2">
            <?php if ($hasLogs): ?>
            <a href="<?= url('payroll', 'monthly', ['employee_id' => $emp['id'], 'year' => $year, 'month' => $month]) ?>"
               class="btn btn-xs btn-outline-primary py-0 px-1"
               data-bs-toggle="tooltip" title="월간 상세">
              <i class="bi bi-search"></i>
            </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>

        <?php if ($totalSum > 0): ?>
        <tr class="table-success fw-bold">
          <td class="ps-3" colspan="2">합 계</td>
          <td class="text-end"><?= minutesToHoursStr($totalPaid) ?></td>
          <td colspan="2"></td>
          <td class="text-end"><?= formatWon($totalBase) ?></td>
          <td class="text-end"><?= formatWon($totalHol) ?></td>
          <td class="text-end"><?= formatWon($totalPrem) ?></td>
          <td class="text-end fs-6"><?= formatWon($totalSum) ?></td>
          <td></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($data !== null): ?>
<?php
// ══ 단일 직원 상세 뷰 ══════════════════════════════════════
$emp = $data['employee'];
$m   = $data['monthly'];
?>

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

    <div class="d-flex gap-2 flex-wrap">
      <a href="<?= url('payroll', 'payslip', ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]) ?>"
         class="btn btn-outline-primary btn-sm" target="_blank">
        <i class="bi bi-file-earmark-text me-1"></i>간이 명세서 (미리보기)
      </a>
      <a href="<?= url('payroll', 'export_csv', ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]) ?>"
         class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>CSV 다운로드
      </a>
    </div>
  </div>
</div>

<?php
// ══ 월 단위 급여명세서 발급 ════════════════════════════════
$existingPayslip    = $existingPayslip    ?? null;
$hasWorkLogs        = $hasWorkLogs        ?? false;
$insNeedsCheck      = $insNeedsCheck      ?? false;
$periodStart        = $periodStart        ?? sprintf('%04d-%02d-01', $year, $month);
$periodEnd          = $periodEnd          ?? date('Y-m-t', strtotime($periodStart));
$defaultPaymentDate = $defaultPaymentDate ?? date('Y-m-10', strtotime($periodStart . ' +1 month'));
$payslipIssued      = $existingPayslip && $existingPayslip['status'] === Payslip::STATUS_ISSUED;
?>
<div class="card border-success mb-4">
  <div class="card-header bg-success text-white">
    <i class="bi bi-file-earmark-check me-1"></i>급여명세서 발급 (월 단위)
  </div>
  <div class="card-body">
    <?php if ($payslipIssued): ?>
      <p class="mb-2">
        <span class="badge bg-success">발급완료</span>
        v<?= (int)$existingPayslip['version'] ?>
        <?php if (!empty($existingPayslip['issued_at'])): ?>
          — <?= h(substr($existingPayslip['issued_at'], 0, 10)) ?>
        <?php endif; ?>
      </p>
      <a href="<?= url('payslip', 'show', ['id' => (int)$existingPayslip['id']]) ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-eye me-1"></i>발급된 명세서 보기
      </a>
      <button class="btn btn-warning btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#correctForm">
        <i class="bi bi-pencil me-1"></i>정정 발급
      </button>
      <div id="correctForm" class="collapse mt-3">
        <form method="POST" action="<?= url('payslip', 'correct') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="original_payslip_id" value="<?= (int)$existingPayslip['id'] ?>">
          <div class="mb-2">
            <label class="form-label small fw-semibold">정정 사유 <span class="text-danger">*</span></label>
            <textarea name="correction_reason" class="form-control form-control-sm" rows="2" required maxlength="1000" placeholder="정정 사유를 입력하세요"></textarea>
          </div>
          <button type="submit" class="btn btn-warning btn-sm">정정 급여명세서 발급</button>
        </form>
      </div>
    <?php elseif (!$hasWorkLogs): ?>
      <p class="small text-muted mb-0">
        <i class="bi bi-info-circle me-1"></i>이 달에 근무기록이 없어 급여명세서를 발급할 수 없습니다.
      </p>
    <?php elseif ($insNeedsCheck): ?>
      <p class="small text-muted mb-0">
        <i class="bi bi-exclamation-triangle me-1"></i>고용보험 공제 여부가 확인되지 않은 주간이 있습니다.
        해당 주간의 고용보험 공제 여부를 먼저 확정해야 월 급여명세서를 발급할 수 있습니다.
      </p>
    <?php else: ?>
      <form method="POST" action="<?= url('payslip', 'preview_monthly') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="employee_id" value="<?= (int)$employeeId ?>">
        <input type="hidden" name="year"  value="<?= (int)$year ?>">
        <input type="hidden" name="month" value="<?= (int)$month ?>">
        <div class="mb-2 small">
          <span class="text-muted">정산 주기:</span> 월급
          <span class="text-muted ms-2">근무기간:</span> <?= (int)$year ?>년 <?= (int)$month ?>월 (<?= h($periodStart) ?> ~ <?= h($periodEnd) ?>)
        </div>
        <div class="mb-2">
          <label class="form-label small">지급 예정일 <span class="text-muted">(실제 임금 지급일)</span></label>
          <input type="date" name="payment_date" class="form-control form-control-sm" style="max-width:200px"
                 value="<?= h($defaultPaymentDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-eye me-1"></i><?= (int)$year ?>년 <?= (int)$month ?>월 급여명세서 미리보기
        </button>
        <div class="form-text small mt-1">클릭 시 명세서 내용을 미리 확인한 후 발급할 수 있습니다.</div>
      </form>
    <?php endif; ?>
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
          <td class="text-end"><?= $m['night_minutes'] > 0 ? minutesToHoursStr($m['night_minutes']) : '—' ?></td>
          <td class="text-end"><?= $m['overtime_minutes'] > 0 ? minutesToHoursStr($m['overtime_minutes']) : '—' ?></td>
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

<?php elseif ($employeeId > 0): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-1"></i>
  <?= h($year) ?>년 <?= h($month) ?>월에 해당하는 근무 기록이 없습니다.
</div>
<?php endif; ?>

<div class="alert alert-warning mt-4 small">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  본 계산 결과는 참고용 예상 금액입니다.
  실제 임금 지급 전 근로계약서·최신 법령·전문가 확인을 권장합니다.
</div>

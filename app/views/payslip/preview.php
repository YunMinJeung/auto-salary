<?php
$weekCount = count($weeklyRows);
$sumBase = $sumHoliday = $sumNight = $sumOvertime = $sumHolPrem = $sumPaidMin = 0;
foreach ($weeklyRows as $wr) {
    $sumBase     += (float)($wr['base_pay']           ?? 0);
    $sumHoliday  += (float)($wr['weekly_holiday_pay'] ?? 0);
    $sumNight    += (float)($wr['night_premium']      ?? 0);
    $sumOvertime += (float)($wr['overtime_premium']   ?? 0);
    $sumHolPrem  += (float)($wr['holiday_premium']    ?? 0);
    $sumPaidMin  += (int)($wr['paid_work_minutes']    ?? 0);
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">
    <i class="bi bi-eye me-2 text-primary"></i>
    <?= (int)$year ?>년 <?= (int)$month ?>월 급여명세서 <span class="text-muted fs-6">미리보기</span>
  </h1>
  <a href="<?= url('payroll', 'monthly', ['employee_id' => (int)$employee['id'], 'year' => $year, 'month' => $month]) ?>"
     class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>돌아가기
  </a>
</div>

<div class="alert alert-info d-flex gap-2 mb-3">
  <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
  <div>
    <strong>발급 전 미리보기입니다.</strong>
    아래 내용을 확인한 후 "이대로 발급" 버튼을 누르면 급여명세서가 확정 저장됩니다.
    발급 후에는 정정 발급만 가능합니다.
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span class="fw-semibold">
      <i class="bi bi-person-fill me-1"></i><?= h($employee['name']) ?>
      · <?= (int)$year ?>년 <?= (int)$month ?>월
      <span class="text-muted small ms-1">(<?= h($periodStart) ?> ~ <?= h($periodEnd) ?>)</span>
    </span>
    <span class="badge bg-secondary">미발급 (미리보기)</span>
  </div>
  <div class="card-body">
    <table class="table table-sm mb-4" style="max-width:480px">
      <tbody>
        <tr><td class="text-muted" style="width:120px">정산 주기</td><td>월급</td></tr>
        <tr><td class="text-muted">근무기간</td><td><?= h($periodStart) ?> ~ <?= h($periodEnd) ?></td></tr>
        <tr><td class="text-muted">지급 예정일</td><td><?= $paymentDate ? h($paymentDate) : '<span class="text-muted">—</span>' ?></td></tr>
      </tbody>
    </table>

    <table class="table table-sm mb-4" style="max-width:480px">
      <tbody>
        <tr><td>세전 급여</td><td class="text-end fw-semibold"><?= formatWon($grossPay) ?></td></tr>
        <tr><td>공제 합계</td><td class="text-end text-danger"><?= $totalDeductions > 0 ? '-' . formatWon($totalDeductions) : formatWon(0) ?></td></tr>
        <tr class="table-success fw-bold"><td>실지급액</td><td class="text-end fs-5"><?= formatWon($netPay) ?></td></tr>
      </tbody>
    </table>

    <h6 class="text-muted text-uppercase small fw-bold mb-2">지급 내역 <span class="text-muted">(월 <?= $weekCount ?>주 합산)</span></h6>
    <table class="table table-sm mb-4" style="max-width:480px">
      <tbody>
        <tr><td>유급 근무시간</td><td class="text-end"><?= minutesToHoursStr($sumPaidMin) ?></td></tr>
        <tr><td>기본급</td><td class="text-end"><?= formatWon($sumBase) ?></td></tr>
        <?php if ($sumHoliday > 0): ?>
        <tr><td>주휴수당</td><td class="text-end"><?= formatWon($sumHoliday) ?></td></tr>
        <?php endif; ?>
        <?php if ($sumNight > 0): ?>
        <tr><td>야간근로 가산수당</td><td class="text-end"><?= formatWon($sumNight) ?></td></tr>
        <?php endif; ?>
        <?php if ($sumOvertime > 0): ?>
        <tr><td>연장근로 가산수당</td><td class="text-end"><?= formatWon($sumOvertime) ?></td></tr>
        <?php endif; ?>
        <?php if ($sumHolPrem > 0): ?>
        <tr><td>휴일근로 가산수당</td><td class="text-end"><?= formatWon($sumHolPrem) ?></td></tr>
        <?php endif; ?>
        <tr class="table-light fw-bold"><td>지급 합계</td><td class="text-end"><?= formatWon($grossPay) ?></td></tr>
      </tbody>
    </table>

    <div class="accordion accordion-flush mb-4" id="weeklyDetail">
      <div class="accordion-item border rounded">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed small fw-semibold" type="button"
                  data-bs-toggle="collapse" data-bs-target="#weeklyRows">
            <i class="bi bi-list-ul me-2"></i>주간 근무 상세 (월 <?= $weekCount ?>주)
          </button>
        </h2>
        <div id="weeklyRows" class="accordion-collapse collapse">
          <div class="accordion-body p-0">
            <table class="table table-sm align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th class="ps-3">주차</th><th>기간</th>
                  <th class="text-end">유급근무</th>
                  <th class="text-end">기본급</th>
                  <th class="text-end">주휴수당</th>
                  <th class="text-end">가산수당</th>
                  <th class="text-end fw-bold pe-3">소계</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($weeklyRows as $i => $wr):
                  $wprem = (float)($wr['night_premium'] ?? 0) + (float)($wr['overtime_premium'] ?? 0) + (float)($wr['holiday_premium'] ?? 0);
                ?>
                <tr>
                  <td class="ps-3"><?= $i + 1 ?>주차</td>
                  <td class="text-nowrap"><?= h($wr['period_start']) ?> ~ <?= h($wr['period_end']) ?></td>
                  <td class="text-end"><?= minutesToHoursStr((int)($wr['paid_work_minutes'] ?? 0)) ?></td>
                  <td class="text-end"><?= formatWon($wr['base_pay'] ?? 0) ?></td>
                  <td class="text-end"><?= (float)($wr['weekly_holiday_pay'] ?? 0) > 0 ? formatWon($wr['weekly_holiday_pay']) : '—' ?></td>
                  <td class="text-end"><?= $wprem > 0 ? formatWon($wprem) : '—' ?></td>
                  <td class="text-end fw-bold pe-3"><?= formatWon($wr['total_pay'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <h6 class="text-muted text-uppercase small fw-bold mb-2">공제 내역 (4대보험)</h6>
    <table class="table table-sm mb-3" style="max-width:480px">
      <tbody>
        <tr><td>국민연금</td><td class="text-end"><?= ($deductions['national_pension'] ?? 0) > 0 ? '-' . formatWon($deductions['national_pension']) : '—' ?></td></tr>
        <tr><td>건강보험</td><td class="text-end"><?= ($deductions['health_insurance'] ?? 0) > 0 ? '-' . formatWon($deductions['health_insurance']) : '—' ?></td></tr>
        <tr><td>장기요양보험</td><td class="text-end"><?= ($deductions['long_term_care'] ?? 0) > 0 ? '-' . formatWon($deductions['long_term_care']) : '—' ?></td></tr>
        <tr><td>고용보험</td><td class="text-end"><?= ($deductions['employment_insurance'] ?? 0) > 0 ? '-' . formatWon($deductions['employment_insurance']) : '—' ?></td></tr>
        <tr class="table-light fw-bold"><td>공제 합계</td><td class="text-end"><?= $totalDeductions > 0 ? '-' . formatWon($totalDeductions) : formatWon(0) ?></td></tr>
      </tbody>
    </table>
  </div>

  <div class="card-footer bg-white d-flex gap-2 align-items-center">
    <form method="POST" action="<?= url('payslip', 'issue_monthly') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="employee_id"   value="<?= (int)$employee['id'] ?>">
      <input type="hidden" name="year"           value="<?= (int)$year ?>">
      <input type="hidden" name="month"          value="<?= (int)$month ?>">
      <input type="hidden" name="payment_date"   value="<?= h($paymentDate ?? '') ?>">
      <button type="submit" class="btn btn-success">
        <i class="bi bi-send-check me-1"></i>이대로 발급
      </button>
    </form>
    <a href="<?= url('payroll', 'monthly', ['employee_id' => (int)$employee['id'], 'year' => $year, 'month' => $month]) ?>"
       class="btn btn-outline-secondary">취소</a>
    <span class="text-muted small ms-2">발급 후에는 정정 발급만 가능합니다.</span>
  </div>
</div>

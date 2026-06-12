<?php
$ded      = $snapshot['deductions']  ?? [];
$weekRows = $snapshot['weekly_rows'] ?? [];
$period   = $snapshot['period']      ?? [];
$gross    = (int)($snapshot['gross_pay'] ?? $payslip['gross_pay']);
$dedTotal = (int)($snapshot['total_deductions'] ?? $payslip['total_deductions']);
$net      = (int)($snapshot['net_pay'] ?? $payslip['net_pay']);

$periodYear  = (int)($period['year']  ?? substr($payslip['period_start'], 0, 4));
$periodMonth = (int)($period['month'] ?? substr($payslip['period_start'], 5, 2));

// 정산 주기 정보: payslip 컬럼 → 스냅샷 → 동적 생성 순으로 폴백 (하위 호환)
$payPeriodType  = $payslip['pay_period_type'] ?? $snapshot['pay_period_type'] ?? Payslip::PERIOD_MONTHLY;
$payPeriodLabel = ($payslip['period_label'] ?? null)
    ?: ($snapshot['period_label'] ?? null)
    ?: Payslip::periodLabel($payPeriodType, $payslip['period_start'], $payslip['period_end']);
$payPeriodTypeLabel = Payslip::PERIOD_TYPE_LABELS[$payPeriodType] ?? $payPeriodType;
$paymentDate = $payslip['payment_date'] ?? ($period['payment_date'] ?? null);

// 월간 지급 항목 합산
$sumBase = 0; $sumHoliday = 0; $sumPrem = 0;
foreach ($weekRows as $wr) {
    $sumBase    += (float)($wr['base_pay'] ?? 0);
    $sumHoliday += (float)($wr['weekly_holiday_pay'] ?? 0);
    $sumPrem    += (float)($wr['night_premium'] ?? 0) + (float)($wr['overtime_premium'] ?? 0) + (float)($wr['holiday_premium'] ?? 0);
}
?>

<div class="card border-0 shadow mb-3 text-center py-3"
     style="background:var(--c-dark);color:#fff;border-radius:16px">
  <div style="font-size:.8rem;opacity:.7">급여명세서 (발급본)</div>
  <div style="font-size:1.05rem;font-weight:700"><?= h($payPeriodLabel) ?></div>
  <div style="font-size:.8rem;opacity:.7">
    버전 v<?= (int)$payslip['version'] ?>
    <?php if (!empty($paymentDate)): ?> · 지급일 <?= h($paymentDate) ?><?php endif; ?>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2 small">
    <div class="d-flex justify-content-between"><span class="text-muted">정산 주기</span><span><?= h($payPeriodTypeLabel) ?></span></div>
    <div class="d-flex justify-content-between"><span class="text-muted">근무기간</span><span><?= h($payslip['period_start']) ?> ~ <?= h($payslip['period_end']) ?></span></div>
    <div class="d-flex justify-content-between"><span class="text-muted">지급일</span><span><?= $paymentDate ? h($paymentDate) : '—' ?></span></div>
  </div>
</div>

<?php if (!empty($payslip['corrected_from_payslip_id'])): ?>
<div class="alert alert-warning small" style="border-radius:12px">
  <i class="bi bi-arrow-repeat me-1"></i>이 명세서는 정정 발급된 급여명세서입니다.
</div>
<?php endif; ?>

<!-- 지급 내역 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small"><i class="bi bi-plus-circle me-1 text-success"></i>지급 내역</div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0 small">
      <tbody>
        <tr><td class="ps-3">기본급</td><td class="text-end pe-3"><?= formatWon($sumBase) ?></td></tr>
        <?php if ($sumHoliday > 0): ?>
        <tr><td class="ps-3">주휴수당</td><td class="text-end pe-3 text-success"><?= formatWon($sumHoliday) ?></td></tr>
        <?php endif; ?>
        <?php if ($sumPrem > 0): ?>
        <tr><td class="ps-3">야간·연장·휴일 가산수당</td><td class="text-end pe-3 text-success"><?= formatWon($sumPrem) ?></td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot class="table-light fw-bold"><tr><td class="ps-3">지급 합계</td><td class="text-end pe-3"><?= formatWon($gross) ?></td></tr></tfoot>
    </table>
  </div>
</div>

<!-- 공제 내역 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small"><i class="bi bi-dash-circle me-1 text-danger"></i>공제 내역</div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0 small">
      <tbody>
        <?php if (($ded['national_pension'] ?? 0) > 0): ?><tr><td class="ps-3">국민연금</td><td class="text-end pe-3"><?= formatWon($ded['national_pension']) ?></td></tr><?php endif; ?>
        <?php if (($ded['health_insurance'] ?? 0) > 0): ?><tr><td class="ps-3">건강보험</td><td class="text-end pe-3"><?= formatWon($ded['health_insurance']) ?></td></tr><?php endif; ?>
        <?php if (($ded['long_term_care'] ?? 0) > 0): ?><tr><td class="ps-3">장기요양보험</td><td class="text-end pe-3"><?= formatWon($ded['long_term_care']) ?></td></tr><?php endif; ?>
        <?php if (($ded['employment_insurance'] ?? 0) > 0): ?><tr><td class="ps-3">고용보험</td><td class="text-end pe-3"><?= formatWon($ded['employment_insurance']) ?></td></tr><?php endif; ?>
        <?php if ($dedTotal <= 0): ?><tr class="text-muted"><td class="ps-3 text-center" colspan="2">공제 항목 없음</td></tr><?php endif; ?>
      </tbody>
      <tfoot class="table-light fw-bold"><tr><td class="ps-3">공제 합계</td><td class="text-end pe-3"><?= formatWon($dedTotal) ?></td></tr></tfoot>
    </table>
  </div>
</div>

<!-- 실수령액 -->
<div class="card border-0 shadow mb-3 text-center py-4" style="border-left:4px solid var(--c-teal) !important">
  <div class="small text-muted mb-1">실지급액</div>
  <div class="fw-bold" style="font-size:2rem;color:var(--c-teal)"><?= formatWon($net) ?></div>
  <div class="small text-muted mt-1"><?= formatWon($gross) ?> − <?= formatWon($dedTotal) ?></div>
</div>

<div class="text-muted small text-center mb-3">
  발급일: <?= h($payslip['issued_at'] ? substr($payslip['issued_at'], 0, 16) : '-') ?>
</div>

<button onclick="window.print()" class="btn btn-outline-secondary w-100 mb-2">
  <i class="bi bi-printer me-1"></i>인쇄 / PDF 저장
</button>
<a href="<?= url('employee') ?>" class="btn btn-outline-secondary w-100 mb-4">
  <i class="bi bi-arrow-left me-1"></i>돌아가기
</a>

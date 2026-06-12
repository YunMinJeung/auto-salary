<?php
$prevMonth = date('Y-m', strtotime("{$year}-{$month}-01 -1 month"));
$nextMonth = date('Y-m', strtotime("{$year}-{$month}-01 +1 month"));
[$prevY, $prevM] = explode('-', $prevMonth);
[$nextY, $nextM] = explode('-', $nextMonth);
?>

<!-- 월 선택 내비 -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="<?= url('employee', 'payslip', ['year' => $prevY, 'month' => (int)$prevM]) ?>"
     class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-chevron-left"></i> 이전달
  </a>
  <span class="fw-bold"><?= $year ?>년 <?= $month ?>월</span>
  <a href="<?= url('employee', 'payslip', ['year' => $nextY, 'month' => (int)$nextM]) ?>"
     class="btn btn-outline-secondary btn-sm">
    다음달 <i class="bi bi-chevron-right"></i>
  </a>
</div>

<!-- 제목 카드 -->
<div class="card border-0 shadow mb-3 text-center py-3"
     style="background:var(--c-dark);color:#fff;border-radius:16px">
  <div style="font-size:.8rem;opacity:.7">예상 급여명세서</div>
  <div style="font-size:1.1rem;font-weight:700"><?= $year ?>년 <?= $month ?>월</div>
  <div style="font-size:.85rem;opacity:.7"><?= h($member['store_name']) ?> · <?= h($member['name']) ?></div>
</div>

<?php if (!$showPay): ?>
<div class="alert alert-secondary text-center">
  <i class="bi bi-eye-slash me-1"></i>점주가 급여 정보 표시를 비공개로 설정했습니다.
</div>
<?php elseif (empty($weeks)): ?>
<div class="alert alert-info text-center">
  <i class="bi bi-calendar-x me-1"></i><?= $year ?>년 <?= $month ?>월 근무 기록이 없습니다.
</div>
<?php else: ?>

<!-- 지급 내역 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small">
    <i class="bi bi-plus-circle me-1 text-success"></i>지급 내역
  </div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0 small">
      <tbody>
        <?php
        $totalBase    = array_sum(array_column($weeks, 'base_pay'));
        $totalHoliday = array_sum(array_column($weeks, 'holiday_pay'));
        $totalPremium = array_sum(array_column($weeks, 'premium_pay'));
        ?>
        <tr>
          <td class="ps-3">기본급</td>
          <td class="text-end pe-3"><?= formatWon($totalBase) ?></td>
        </tr>
        <?php if ($totalHoliday > 0): ?>
        <tr>
          <td class="ps-3">주휴수당 (추정)</td>
          <td class="text-end pe-3 text-success"><?= formatWon($totalHoliday) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($totalPremium > 0): ?>
        <tr>
          <td class="ps-3">야간·연장·휴일 가산수당</td>
          <td class="text-end pe-3 text-success"><?= formatWon($totalPremium) ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td class="ps-3">지급 합계</td>
          <td class="text-end pe-3"><?= formatWon($grossPay) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- 공제 내역 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small">
    <i class="bi bi-dash-circle me-1 text-danger"></i>공제 내역
  </div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0 small">
      <tbody>
        <?php if (!empty($deductions['national_pension'])): ?>
        <tr>
          <td class="ps-3">국민연금 <span class="text-muted">(4.75%)</span></td>
          <td class="text-end pe-3"><?= formatWon($deductions['national_pension']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($deductions['health_insurance'])): ?>
        <tr>
          <td class="ps-3">건강보험 <span class="text-muted">(3.595%)</span></td>
          <td class="text-end pe-3"><?= formatWon($deductions['health_insurance']) ?></td>
        </tr>
        <tr>
          <td class="ps-3">장기요양보험 <span class="text-muted">(건강보험료의 13.14%)</span></td>
          <td class="text-end pe-3"><?= formatWon($deductions['long_term_care']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($deductions['employment_insurance'])): ?>
        <tr>
          <td class="ps-3">고용보험 <span class="text-muted">(0.9%)</span></td>
          <td class="text-end pe-3"><?= formatWon($deductions['employment_insurance']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (empty($deductions['total'])): ?>
        <tr class="text-muted">
          <td class="ps-3 text-center" colspan="2">공제 항목 없음</td>
        </tr>
        <?php endif; ?>
      </tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td class="ps-3">공제 합계</td>
          <td class="text-end pe-3"><?= formatWon($deductions['total'] ?? 0) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- 실수령액 -->
<div class="card border-0 shadow mb-3 text-center py-4" style="border-left:4px solid var(--c-teal) !important">
  <div class="small text-muted mb-1">예상 실수령액</div>
  <div class="fw-bold" style="font-size:2rem;color:var(--c-teal)"><?= formatWon($netPay) ?></div>
  <div class="small text-muted mt-1">
    <?= formatWon($grossPay) ?> − <?= formatWon($deductions['total'] ?? 0) ?>
  </div>
</div>

<!-- 주별 근무 상세 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small">
    <i class="bi bi-calendar3-week me-1"></i>주별 근무 내역
  </div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th class="ps-3">기간</th>
          <th class="text-center">일수</th>
          <th class="text-end">시간</th>
          <th class="text-end pe-3">금액</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($weeks as $w): ?>
        <tr>
          <td class="ps-3 text-nowrap">
            <?= substr($w['period_start'], 5) ?> ~ <?= substr($w['period_end'], 5) ?>
          </td>
          <td class="text-center"><?= $w['work_days'] ?>일</td>
          <td class="text-end"><?= minutesToHoursStr($w['paid_minutes']) ?></td>
          <td class="text-end pe-3 fw-semibold"><?= formatWon($w['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<div class="alert alert-warning small mb-3" style="border-radius:12px">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  이 명세서는 <strong>예상 금액</strong>입니다. 야간·연장·휴일 가산수당이 반영된 점주 계산과 동일한 방식으로 산정됩니다.
  최종 급여는 점주가 확정합니다.
</div>

<a href="<?= url('employee') ?>" class="btn btn-outline-secondary w-100 mb-4">
  <i class="bi bi-arrow-left me-1"></i>돌아가기
</a>

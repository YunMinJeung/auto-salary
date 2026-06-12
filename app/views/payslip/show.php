<?php
$statusLabels = Payslip::STATUS_LABELS;
$statusBadges = Payslip::STATUS_BADGES;

$emp      = $snapshot['employee']    ?? [];
$ded      = $snapshot['deductions']  ?? [];
$monthly  = $snapshot['monthly']     ?? [];
$weekRows = $snapshot['weekly_rows'] ?? [];
$period   = $snapshot['period']      ?? [];
$gross    = (int)($snapshot['gross_pay'] ?? $payslip['gross_pay']);
$dedTotal = (int)($snapshot['total_deductions'] ?? $payslip['total_deductions']);
$net      = (int)($snapshot['net_pay'] ?? $payslip['net_pay']);

// 월 표기: 스냅샷 period 우선, 없으면 기간에서 도출
$periodYear  = (int)($period['year']  ?? substr($payslip['period_start'], 0, 4));
$periodMonth = (int)($period['month'] ?? substr($payslip['period_start'], 5, 2));
$weekCount   = (int)($monthly['week_count'] ?? count($weekRows));

// 정산 주기 정보: payslip 컬럼 → 스냅샷 → 동적 생성 순으로 폴백 (하위 호환)
$payPeriodType  = $payslip['pay_period_type'] ?? $snapshot['pay_period_type'] ?? Payslip::PERIOD_MONTHLY;
$payPeriodLabel = ($payslip['period_label'] ?? null)
    ?: ($snapshot['period_label'] ?? null)
    ?: Payslip::periodLabel($payPeriodType, $payslip['period_start'], $payslip['period_end']);
$payPeriodTypeLabel = Payslip::PERIOD_TYPE_LABELS[$payPeriodType] ?? $payPeriodType;
$paymentDate = $payslip['payment_date'] ?? ($period['payment_date'] ?? null);

// 월간 지급 항목 합산 (주간 rows 집계)
$sumBase = 0; $sumHoliday = 0; $sumNight = 0; $sumOvertime = 0; $sumHolPrem = 0; $sumPaidMin = 0;
foreach ($weekRows as $wr) {
    $sumBase     += (float)($wr['base_pay'] ?? 0);
    $sumHoliday  += (float)($wr['weekly_holiday_pay'] ?? 0);
    $sumNight    += (float)($wr['night_premium'] ?? 0);
    $sumOvertime += (float)($wr['overtime_premium'] ?? 0);
    $sumHolPrem  += (float)($wr['holiday_premium'] ?? 0);
    $sumPaidMin  += (int)($wr['paid_work_minutes'] ?? 0);
}

$isLocked = Payslip::isLocked($payslip);
$canAct   = ($payslip['status'] === Payslip::STATUS_ISSUED);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-file-earmark-text me-2 text-success"></i>
    <?= h($payPeriodLabel) ?>명세서 <span class="text-muted fs-6">(v<?= (int)$payslip['version'] ?>)</span>
  </h1>
  <a href="<?= url('payslip', 'index') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-list-ul me-1"></i>목록
  </a>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span class="fw-semibold">
      <i class="bi bi-person-fill me-1"></i><?= h($emp['name'] ?? '-') ?>
      · <?= $periodYear ?>년 <?= $periodMonth ?>월
      <span class="text-muted small ms-1">(<?= h($payslip['period_start']) ?> ~ <?= h($payslip['period_end']) ?>)</span>
    </span>
    <span>
      <span class="badge bg-light text-dark border me-1">버전 v<?= (int)$payslip['version'] ?></span>
      <span class="badge <?= $statusBadges[$payslip['status']] ?? 'bg-secondary' ?>">
        <?= h($statusLabels[$payslip['status']] ?? $payslip['status']) ?>
      </span>
    </span>
  </div>
  <div class="card-body">

    <?php if (!empty($payslip['corrected_from_payslip_id'])): ?>
    <div class="alert alert-warning small">
      <i class="bi bi-arrow-repeat me-1"></i>이 명세서는 정정 발급된 급여명세서입니다.
      <?php if (!empty($payslip['correction_reason'])): ?><br>정정 사유: <?= h($payslip['correction_reason']) ?><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($payslip['status'] === Payslip::STATUS_CANCELLED): ?>
    <div class="alert alert-danger small">
      <i class="bi bi-x-octagon me-1"></i>이 급여명세서는 취소되었습니다.
      <?php if (!empty($payslip['cancellation_reason'])): ?><br>취소 사유: <?= h($payslip['cancellation_reason']) ?><?php endif; ?>
    </div>
    <?php elseif ($payslip['status'] === Payslip::STATUS_CORRECTED): ?>
    <div class="alert alert-secondary small">
      <i class="bi bi-info-circle me-1"></i>이 명세서는 정정되어 새 버전으로 대체되었습니다. (수정 불가)
    </div>
    <?php endif; ?>

    <!-- 발급 정보 -->
    <table class="table table-sm mb-4" style="max-width:480px">
      <tbody>
        <tr><td class="text-muted" style="width:120px">정산 주기</td><td><?= h($payPeriodTypeLabel) ?></td></tr>
        <tr><td class="text-muted">근무기간</td><td><?= h($payslip['period_start']) ?> ~ <?= h($payslip['period_end']) ?></td></tr>
        <tr><td class="text-muted">지급일</td><td><?= $paymentDate ? h($paymentDate) : '<span class="text-muted">—</span>' ?></td></tr>
      </tbody>
    </table>

    <!-- 급여 요약 -->
    <table class="table table-sm mb-4" style="max-width:480px">
      <tbody>
        <tr><td>세전 급여</td><td class="text-end fw-semibold"><?= formatWon($gross) ?></td></tr>
        <tr><td>공제 합계</td><td class="text-end text-danger"><?= $dedTotal > 0 ? '-' . formatWon($dedTotal) : formatWon(0) ?></td></tr>
        <tr class="table-success fw-bold"><td>실지급액</td><td class="text-end fs-5"><?= formatWon($net) ?></td></tr>
      </tbody>
    </table>

    <!-- 지급 상세 (월간 합산) -->
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
        <tr class="table-light fw-bold"><td>지급 합계</td><td class="text-end"><?= formatWon($gross) ?></td></tr>
      </tbody>
    </table>

    <!-- 주간 근무 상세 (접기) -->
    <?php if (!empty($weekRows)): ?>
    <div class="accordion accordion-flush mb-4" id="weeklyDetail">
      <div class="accordion-item border rounded">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed small fw-semibold" type="button"
                  data-bs-toggle="collapse" data-bs-target="#weeklyRows">
            <i class="bi bi-list-ul me-2"></i>주간 근무 상세 (월 <?= $weekCount ?>주)
          </button>
        </h2>
        <div id="weeklyRows" class="accordion-collapse collapse" data-bs-parent="#weeklyDetail">
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
                <?php foreach ($weekRows as $i => $wr):
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
    <?php endif; ?>

    <!-- 공제 상세 -->
    <h6 class="text-muted text-uppercase small fw-bold mb-2">공제 내역 (4대보험)</h6>
    <table class="table table-sm mb-4" style="max-width:480px">
      <tbody>
        <tr><td>국민연금</td><td class="text-end"><?= ($ded['national_pension'] ?? 0) > 0 ? '-' . formatWon($ded['national_pension']) : '—' ?></td></tr>
        <tr><td>건강보험</td><td class="text-end"><?= ($ded['health_insurance'] ?? 0) > 0 ? '-' . formatWon($ded['health_insurance']) : '—' ?></td></tr>
        <tr><td>장기요양보험</td><td class="text-end"><?= ($ded['long_term_care'] ?? 0) > 0 ? '-' . formatWon($ded['long_term_care']) : '—' ?></td></tr>
        <tr><td>고용보험</td><td class="text-end"><?= ($ded['employment_insurance'] ?? 0) > 0 ? '-' . formatWon($ded['employment_insurance']) : '—' ?></td></tr>
        <tr class="table-light fw-bold"><td>공제 합계</td><td class="text-end"><?= $dedTotal > 0 ? '-' . formatWon($dedTotal) : formatWon(0) ?></td></tr>
      </tbody>
    </table>

    <div class="text-muted small">
      발급일: <?= h($payslip['issued_at'] ?? '-') ?>
      <?php if (!empty($snapshot['issued_by_name'])): ?> · 발급자: <?= h($snapshot['issued_by_name']) ?><?php endif; ?>
      <?php if (!empty($payslip['payment_date'])): ?> · 지급일: <?= h($payslip['payment_date']) ?><?php endif; ?>
    </div>
  </div>

  <!-- 액션 -->
  <div class="card-footer bg-white">
    <?php if ($canAct): ?>
      <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#correctModal">
        <i class="bi bi-pencil-square me-1"></i>정정 발급
      </button>
      <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
        <i class="bi bi-x-circle me-1"></i>취소
      </button>
    <?php else: ?>
      <span class="text-muted small"><i class="bi bi-lock-fill me-1"></i>수정 불가 (<?= h($statusLabels[$payslip['status']] ?? $payslip['status']) ?>)</span>
    <?php endif; ?>
  </div>
</div>

<?php if ($canAct): ?>
<!-- 정정 발급 모달 -->
<div class="modal fade" id="correctModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="POST" action="<?= url('payslip', 'correct') ?>" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="original_payslip_id" value="<?= (int)$payslip['id'] ?>">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-warning"></i>정정 발급</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">

        <!-- 1단계: 근무기록 수정 -->
        <div class="d-flex gap-3 border rounded p-3 mb-3 bg-light align-items-start">
          <div class="text-primary fw-bold fs-5 lh-1 mt-1">①</div>
          <div class="flex-grow-1">
            <p class="fw-semibold small mb-1">근무기록 수정 <span class="text-muted fw-normal">(필요한 경우)</span></p>
            <p class="small text-muted mb-2">
              잘못된 근무기록이 있다면 먼저 수정하세요.<br>
              정정 발급은 <strong>현재 저장된 근무기록</strong>을 기준으로 재계산합니다.
            </p>
            <a href="<?= url('work_logs', 'index', ['employee_id' => (int)$payslip['employee_id']]) ?>"
               class="btn btn-sm btn-outline-primary" target="_blank">
              <i class="bi bi-box-arrow-up-right me-1"></i><?= h($emp['name'] ?? '직원') ?> 근무기록 수정
            </a>
          </div>
        </div>

        <!-- 2단계: 현재 발급금액 확인 -->
        <div class="d-flex gap-3 border rounded p-3 mb-3 align-items-start">
          <div class="text-secondary fw-bold fs-5 lh-1 mt-1">②</div>
          <div class="flex-grow-1">
            <p class="fw-semibold small mb-2">현재 발급된 금액 <span class="text-muted fw-normal">(v<?= (int)$payslip['version'] ?>)</span></p>
            <div class="row g-2 text-center small mb-2">
              <div class="col-4">
                <div class="text-muted mb-1">세전 급여</div>
                <div class="fw-semibold"><?= formatWon($gross) ?></div>
              </div>
              <div class="col-4">
                <div class="text-muted mb-1">공제 합계</div>
                <div class="fw-semibold text-danger"><?= $dedTotal > 0 ? '−'.formatWon($dedTotal) : '—' ?></div>
              </div>
              <div class="col-4">
                <div class="text-muted mb-1">실지급액</div>
                <div class="fw-semibold" style="color:var(--c-teal)"><?= formatWon($net) ?></div>
              </div>
            </div>
            <p class="text-muted small mb-0">
              <i class="bi bi-arrow-right me-1"></i>정정 발급 후 v<?= (int)$payslip['version'] + 1 ?>이 현재 근무기록 기준으로 재계산되어 발급됩니다.
            </p>
          </div>
        </div>

        <!-- 3단계: 정정 사유 -->
        <div class="d-flex gap-3 align-items-start">
          <div class="text-warning fw-bold fs-5 lh-1 mt-1">③</div>
          <div class="flex-grow-1">
            <label class="form-label fw-semibold small mb-1">정정 사유 <span class="text-danger">*</span></label>
            <textarea name="correction_reason" class="form-control" rows="3" required maxlength="1000"
                      placeholder="예) 5월 3주차 야간 근무 1시간 누락으로 인한 정정"></textarea>
          </div>
        </div>

      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">닫기</button>
        <button type="submit" class="btn btn-warning btn-sm">
          <i class="bi bi-file-earmark-plus me-1"></i>v<?= (int)$payslip['version'] + 1 ?> 정정 발급
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 취소 모달 -->
<div class="modal fade" id="cancelModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="<?= url('payslip', 'cancel') ?>" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="payslip_id" value="<?= (int)$payslip['id'] ?>">
      <div class="modal-header"><h6 class="modal-title">급여명세서 취소</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="small text-muted">취소 시 이 급여명세서는 취소됨(CANCELLED) 상태가 되며 되돌릴 수 없습니다.</p>
        <label class="form-label fw-semibold small">취소 사유 <span class="text-danger">*</span></label>
        <textarea name="cancellation_reason" class="form-control" rows="3" required maxlength="1000" placeholder="취소 사유를 입력하세요"></textarea>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">닫기</button>
        <button type="submit" class="btn btn-danger btn-sm">취소 처리</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- 버전 이력 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold small"><i class="bi bi-clock-history me-1"></i>발급 이력 (해당 직원)</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0 small">
      <thead class="table-light">
        <tr><th class="ps-3">기간</th><th class="text-center">버전</th><th class="text-center">상태</th>
          <th class="text-end">실지급</th><th>발급일</th><th class="pe-3"></th></tr>
      </thead>
      <tbody>
        <?php foreach ($history as $hp): ?>
        <tr class="<?= (int)$hp['id'] === (int)$payslip['id'] ? 'table-active' : '' ?>">
          <td class="ps-3 text-nowrap"><?= h($hp['period_start']) ?> ~ <?= h($hp['period_end']) ?></td>
          <td class="text-center">v<?= (int)$hp['version'] ?></td>
          <td class="text-center">
            <span class="badge <?= $statusBadges[$hp['status']] ?? 'bg-secondary' ?>"><?= h($statusLabels[$hp['status']] ?? $hp['status']) ?></span>
          </td>
          <td class="text-end"><?= formatWon($hp['net_pay']) ?></td>
          <td class="text-nowrap"><?= h($hp['issued_at'] ? substr($hp['issued_at'], 0, 10) : '—') ?></td>
          <td class="pe-3 text-end">
            <?php if ((int)$hp['id'] !== (int)$payslip['id']): ?>
            <a href="<?= url('payslip', 'show', ['id' => (int)$hp['id']]) ?>" class="btn btn-xs btn-outline-primary py-0 px-2">보기</a>
            <?php else: ?><span class="text-muted">현재</span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

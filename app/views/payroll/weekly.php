<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-cash-coin me-2 text-success"></i>주간 급여 계산</h1>
  <?php if ($employeeId > 0): ?>
  <a href="<?= url('payroll', 'index', ['week_date' => $weekDate]) ?>"
     class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-list-ul me-1"></i>전체 보기
  </a>
  <?php endif; ?>
</div>

<!-- 조회 폼 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" action="<?= url('payroll') ?>" class="row g-3 align-items-end">
      <input type="hidden" name="c" value="payroll">
      <div class="col-12 col-sm-4 col-md-3">
        <label class="form-label fw-semibold">직원</label>
        <select name="employee_id" class="form-select">
          <option value="">전체 직원</option>
          <?php foreach ($employees as $emp): ?>
          <option value="<?= $emp['id'] ?>"
                  <?= $employeeId === $emp['id'] ? 'selected' : '' ?>>
            <?= h($emp['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-sm-5 col-md-4">
        <label class="form-label fw-semibold">
          해당 주의 날짜
          <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
             title="선택한 날짜가 포함된 주(월~일)를 계산합니다."></i>
        </label>
        <input type="date" name="week_date" class="form-control"
               value="<?= h($weekDate) ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-calculator me-1"></i>계산
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($allResults !== null): ?>
<?php
// ══ 전체 직원 요약 뷰 ══════════════════════════════════════

$hasAny     = false;
$totalPaid  = 0; $totalBase = 0; $totalHol  = 0;
$totalPrem  = 0; $totalSum  = 0;
foreach ($allResults as $r) {
    if ($r['has_logs']) { $hasAny = true; break; }
}
?>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
    <span>
      <i class="bi bi-people-fill me-1 text-primary"></i>
      <?= h($periodStart) ?> ~ <?= h($periodEnd) ?>
      <span class="text-muted small ms-2">(<?= count($allResults) ?>명)</span>
    </span>
    <span class="text-muted small">
      <?= date('Y') ?>년 최저시급: <?= number_format($periodMinWage) ?>원
    </span>
  </div>
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
        <?php foreach ($allResults as $r):
          $emp  = $r['employee'];
          $m    = $r;
          $prem = ($m['night_premium'] ?? 0) + ($m['overtime_premium'] ?? 0) + ($m['holiday_premium'] ?? 0);
          $belowMin = $emp['hourly_wage'] < $periodMinWage;
          if ($r['has_logs']) {
              $totalPaid += $m['paid_work_minutes'];
              $totalBase += $m['base_pay'];
              $totalHol  += $m['weekly_holiday_pay'];
              $totalPrem += $prem;
              $totalSum  += $m['total_pay'];
          }
        ?>
        <tr class="<?= !$r['has_logs'] ? 'text-muted' : '' ?>">
          <td class="ps-3">
            <?php if ($belowMin && $r['has_logs']): ?>
            <i class="bi bi-exclamation-triangle-fill text-danger me-1"
               data-bs-toggle="tooltip"
               title="시급(<?= number_format($emp['hourly_wage']) ?>원)이 최저시급보다 낮습니다"></i>
            <?php endif; ?>
            <span class="<?= $r['has_logs'] ? 'fw-semibold' : '' ?>">
              <?= h($emp['name']) ?>
            </span>
          </td>
          <td class="text-end"><?= number_format($emp['hourly_wage']) ?>원</td>
          <?php if ($r['has_logs']): ?>
          <td class="text-end"><?= minutesToHoursStr($m['paid_work_minutes']) ?></td>
          <td class="text-end text-primary"><?= $m['night_minutes'] > 0    ? minutesToHoursStr($m['night_minutes'])    : '—' ?></td>
          <td class="text-end"><?=              $m['overtime_minutes'] > 0 ? minutesToHoursStr($m['overtime_minutes']) : '—' ?></td>
          <td class="text-end"><?= formatWon($m['base_pay']) ?></td>
          <td class="text-end text-success"><?= $m['weekly_holiday_pay'] > 0 ? formatWon($m['weekly_holiday_pay']) : '—' ?></td>
          <td class="text-end"><?= $prem > 0 ? formatWon($prem) : '—' ?></td>
          <td class="text-end fw-bold"><?= formatWon($m['total_pay']) ?></td>
          <?php else: ?>
          <td colspan="7" class="text-center text-muted small fst-italic">이번 주 근무 기록 없음</td>
          <?php endif; ?>
          <td class="pe-2">
            <div class="d-flex gap-1 justify-content-end">
              <?php if ($r['has_logs']): ?>
              <a href="<?= url('payroll', 'index', ['employee_id' => $emp['id'], 'week_date' => $weekDate]) ?>"
                 class="btn btn-xs btn-outline-primary py-0 px-1"
                 data-bs-toggle="tooltip" title="주간 상세">
                <i class="bi bi-search"></i>
              </a>
              <?php else: ?>
              <a href="<?= url('work_logs', 'create', ['employee_id' => $emp['id']]) ?>"
                 class="btn btn-xs btn-outline-secondary py-0 px-1"
                 data-bs-toggle="tooltip" title="근무 기록 추가">
                <i class="bi bi-plus"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>

        <?php if ($hasAny): ?>
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
  <?php if (!$hasAny && !empty($allResults)): ?>
  <div class="card-body text-center py-4 text-muted">
    <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
    이번 주(<?= h($periodStart) ?> ~ <?= h($periodEnd) ?>)에 근무 기록이 없습니다.
  </div>
  <?php endif; ?>
</div>

<?php elseif ($result !== null): ?>
<?php
// ══ 단일 직원 상세 뷰 ══════════════════════════════════════
$emp = $result['employee'];
?>

<!-- 계산 결과 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <span class="fw-bold">
      <i class="bi bi-person-fill me-1"></i><?= h($emp['name']) ?>
      · <?= h($result['period_start']) ?> ~ <?= h($result['period_end']) ?>
    </span>
    <span class="badge bg-white text-primary fs-6">
      총 <?= formatWon($result['total_pay']) ?>
    </span>
  </div>
  <div class="card-body">

    <!-- 시간 요약 -->
    <h6 class="text-muted text-uppercase small fw-bold mb-3">근무 시간 내역</h6>
    <div class="row g-2 mb-4">
      <?php
      $timeItems = [
          ['총 근무시간',   $result['total_work_minutes'],  ''],
          ['휴게시간',       $result['break_minutes'],       'text-muted'],
          ['유급 근무시간', $result['paid_work_minutes'],   'fw-bold'],
          ['야간근로',       $result['night_minutes'],       'text-primary'],
          ['연장근로',       $result['overtime_minutes'],    'text-warning-emphasis'],
          ['휴일근로',       $result['holiday_minutes'],     'text-danger'],
      ];
      foreach ($timeItems as [$label, $min, $cls]):
      ?>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="border rounded p-2 text-center h-100">
          <div class="small text-muted"><?= $label ?></div>
          <div class="fw-semibold <?= $cls ?>"><?= minutesToHoursStr($min) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- 급여 내역 -->
    <h6 class="text-muted text-uppercase small fw-bold mb-3">급여 내역</h6>
    <table class="table table-sm mb-4" style="max-width:500px">
      <tbody>
        <tr>
          <td>기본급
            <?php if (!empty($result['is_trial_period'])): ?>
            <span class="badge bg-warning text-dark ms-1 small">수습</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php $dispWage = $result['effective_wage'] ?? ($emp['hourly_wage']); ?>
            <?= minutesToHoursStr($result['paid_work_minutes']) ?> × <?= number_format($dispWage) ?>원
            <?php if (!empty($result['is_trial_period'])): ?>
            <span class="text-muted small">(정규 <?= number_format($emp['hourly_wage']) ?>원)</span>
            <?php elseif ($dispWage !== (int)$emp['hourly_wage']): ?>
            <span class="text-muted small">(현재 시급 <?= number_format($emp['hourly_wage']) ?>원)</span>
            <?php endif; ?>
          </td>
          <td class="text-end fw-semibold"><?= formatWon($result['base_pay']) ?></td>
        </tr>

        <?php if ($result['holiday_enabled']): ?>
        <tr class="<?= $result['weekly_holiday_hours'] > 0 ? '' : 'text-muted' ?>">
          <td>주휴수당</td>
          <td class="text-end">
            <?php if ($result['weekly_holiday_hours'] > 0): ?>
              <?= number_format($result['weekly_holiday_hours'], 2) ?>시간 × <?= number_format($dispWage) ?>원
            <?php else: ?>
              미발생
            <?php endif; ?>
          </td>
          <td class="text-end fw-semibold">
            <?= $result['weekly_holiday_hours'] > 0 ? formatWon($result['weekly_holiday_pay']) : '—' ?>
          </td>
        </tr>
        <?php endif; ?>

        <?php if ($result['night_minutes'] > 0 || $settings['apply_night_premium']): ?>
        <tr class="<?= $result['night_minutes'] > 0 ? '' : 'text-muted' ?>">
          <td>야간근로 가산수당 <span class="text-muted small">(×0.5)</span></td>
          <td class="text-end">
            <?= $result['night_minutes'] > 0 ? minutesToHoursStr($result['night_minutes']) . ' × 0.5' : '없음' ?>
          </td>
          <td class="text-end fw-semibold">
            <?= $result['night_minutes'] > 0 ? formatWon($result['night_premium']) : '—' ?>
          </td>
        </tr>
        <?php endif; ?>

        <?php if ($result['overtime_minutes'] > 0 || $settings['apply_overtime_premium']): ?>
        <tr class="<?= $result['overtime_minutes'] > 0 ? '' : 'text-muted' ?>">
          <td>연장근로 가산수당 <span class="text-muted small">(×0.5)</span></td>
          <td class="text-end">
            <?= $result['overtime_minutes'] > 0 ? minutesToHoursStr($result['overtime_minutes']) . ' × 0.5' : '없음' ?>
          </td>
          <td class="text-end fw-semibold">
            <?= $result['overtime_minutes'] > 0 ? formatWon($result['overtime_premium']) : '—' ?>
          </td>
        </tr>
        <?php endif; ?>

        <?php if ($result['holiday_minutes'] > 0 || $settings['apply_holiday_premium']): ?>
        <tr class="<?= $result['holiday_minutes'] > 0 ? '' : 'text-muted' ?>">
          <td>휴일근로 가산수당 <span class="text-muted small">(×0.5)</span></td>
          <td class="text-end">
            <?= $result['holiday_minutes'] > 0 ? minutesToHoursStr($result['holiday_minutes']) . ' × 0.5' : '없음' ?>
          </td>
          <td class="text-end fw-semibold">
            <?= $result['holiday_minutes'] > 0 ? formatWon($result['holiday_premium']) : '—' ?>
          </td>
        </tr>
        <?php endif; ?>

        <?php if (($result['furlough_minutes'] ?? 0) > 0): ?>
        <tr class="<?= ($result['furlough_pay'] ?? 0) > 0 ? 'table-warning' : 'text-muted' ?>">
          <td>
            휴업수당 <span class="text-muted small">(사업주 귀책 조기퇴근 × 70%)</span>
          </td>
          <td class="text-end">
            <?= minutesToHoursStr($result['furlough_minutes']) ?> × 70%
          </td>
          <td class="text-end fw-semibold">
            <?= ($result['furlough_pay'] ?? 0) > 0 ? formatWon($result['furlough_pay']) : '미적용 (5인 미만)' ?>
          </td>
        </tr>
        <?php endif; ?>

        <tr class="table-light fw-bold">
          <td colspan="2">세전 합계 (지급 총액)</td>
          <td class="text-end fs-6"><?= formatWon($result['gross_pay'] ?? $result['total_pay']) ?></td>
        </tr>
      </tbody>
    </table>

    <?php
    // ── 공제 항목 / 실지급액 ─────────────────────────────────
    $insStatus = $savedResult['employment_insurance_deduction_status']
        ?? ($result['suggested_ins_status'] ?? 'APPLIED');
    $empInsDeducted = ($insStatus !== 'EXCLUDED'); // NEEDS_CHECK·APPLIED 시 공제 가정 표시
    $dedTotalShown  = $empInsDeducted
        ? ($result['deduction_total'] ?? 0)
        : ($result['deduction_total_exc_emp_ins'] ?? 0);
    $netShown = $empInsDeducted
        ? ($result['net_pay'] ?? $result['total_pay'])
        : ($result['net_pay_exc_emp_ins'] ?? $result['total_pay']);
    ?>

    <h6 class="text-muted text-uppercase small fw-bold mb-3">공제 항목 <span class="text-muted">(4대보험 · 예상)</span></h6>
    <table class="table table-sm mb-4" style="max-width:500px">
      <tbody>
        <tr>
          <td>국민연금</td>
          <td class="text-end fw-semibold"><?= ($result['deduction_pension'] ?? 0) > 0 ? '-' . formatWon($result['deduction_pension']) : '—' ?></td>
        </tr>
        <tr>
          <td>건강보험</td>
          <td class="text-end fw-semibold"><?= ($result['deduction_health'] ?? 0) > 0 ? '-' . formatWon($result['deduction_health']) : '—' ?></td>
        </tr>
        <tr>
          <td>장기요양보험</td>
          <td class="text-end fw-semibold"><?= ($result['deduction_care'] ?? 0) > 0 ? '-' . formatWon($result['deduction_care']) : '—' ?></td>
        </tr>
        <tr class="<?= $insStatus === 'NEEDS_CHECK' ? 'table-warning' : '' ?>">
          <td>
            고용보험
            <?php if ($insStatus === 'NEEDS_CHECK'): ?>
              <span class="badge bg-warning text-dark ms-1 small">확인 필요</span>
            <?php elseif ($insStatus === 'EXCLUDED'): ?>
              <span class="badge bg-secondary ms-1 small">공제 제외</span>
            <?php endif; ?>
          </td>
          <td class="text-end fw-semibold">
            <?php if ($insStatus === 'EXCLUDED'): ?>
              미공제
            <?php else: ?>
              <?= ($result['deduction_employment'] ?? 0) > 0 ? '-' . formatWon($result['deduction_employment']) : '—' ?>
            <?php endif; ?>
          </td>
        </tr>
        <tr class="text-muted">
          <td>소득세 <span class="small">(별도 산정)</span></td>
          <td class="text-end">—</td>
        </tr>
        <tr class="text-muted">
          <td>지방소득세 <span class="small">(별도 산정)</span></td>
          <td class="text-end">—</td>
        </tr>
        <tr class="table-light fw-bold">
          <td>총 공제액</td>
          <td class="text-end"><?= $dedTotalShown > 0 ? '-' . formatWon($dedTotalShown) : formatWon(0) ?></td>
        </tr>
        <tr class="table-success fw-bold">
          <td>실지급액 (예상)</td>
          <td class="text-end fs-5"><?= formatWon($netShown) ?></td>
        </tr>
      </tbody>
    </table>

    <?php if ($insStatus === 'NEEDS_CHECK'): ?>
    <div class="alert alert-warning small">
      <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>고용보험 공제 여부 확인 필요</strong><br>
      이 직원은 다른 사업장에서 고용보험 가입 중으로 표시되어 있습니다.
      고용보험은 중복 취득이 제한될 수 있으므로 이 사업장에서 공제할지 확인해 주세요.
      <div class="mt-2">
        <span class="me-3">예상 실지급액 (고용보험 공제 시): <strong><?= formatWon($result['net_pay'] ?? $result['total_pay']) ?></strong></span>
        <span>예상 실지급액 (고용보험 미공제 시): <strong><?= formatWon($result['net_pay_exc_emp_ins'] ?? $result['total_pay']) ?></strong></span>
      </div>
    </div>

    <?php if (!empty($savedResult['id'])): ?>
    <form method="POST" action="<?= url('payroll', 'update_ins_status') ?>" class="card card-body bg-light border-warning mb-3">
      <?= csrf_field() ?>
      <input type="hidden" name="payroll_id" value="<?= (int)$savedResult['id'] ?>">
      <label class="form-label fw-semibold small mb-1">고용보험 공제 여부 선택</label>
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <select name="ins_status" class="form-select form-select-sm">
            <option value="APPLIED">이 사업장에서 고용보험 공제 적용</option>
            <option value="EXCLUDED">이 사업장에서는 고용보험 공제 제외</option>
          </select>
        </div>
        <div class="col-12 col-md-5">
          <input type="text" name="reason" class="form-control form-control-sm" placeholder="사유 (선택)" maxlength="500">
        </div>
        <div class="col-12 col-md-2 d-grid">
          <button type="submit" class="btn btn-sm btn-warning">확정</button>
        </div>
      </div>
    </form>
    <?php else: ?>
    <div class="alert alert-light border small">
      <i class="bi bi-info-circle me-1"></i>
      먼저 아래 <strong>계산 결과 저장</strong>을 눌러 결과를 저장하면 고용보험 공제 여부를 선택할 수 있습니다.
    </div>
    <?php endif; ?>
    <?php elseif ($insStatus === 'EXCLUDED'): ?>
    <div class="alert alert-secondary small">
      <i class="bi bi-info-circle me-1"></i>
      이 사업장에서는 고용보험을 공제하지 않도록 설정되어 있습니다.
      <?php if (!empty($savedResult['ins_status_reason'])): ?>
        (사유: <?= h($savedResult['ins_status_reason']) ?>)
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 계산 사유 -->
    <h6 class="text-muted text-uppercase small fw-bold mb-2">계산 사유</h6>
    <?php foreach ($result['reasons'] as $reason): ?>
    <?php
      $icon = match($reason['type']) {
          'success' => 'bi-check-circle-fill text-success',
          'warning' => 'bi-exclamation-triangle-fill text-warning',
          'muted'   => 'bi-dash-circle text-muted',
          default   => 'bi-info-circle-fill text-info',
      };
    ?>
    <div class="d-flex gap-2 mb-1 small">
      <i class="bi <?= $icon ?> mt-1 flex-shrink-0"></i>
      <span><?= h($reason['text']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 일별 상세 내역 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-list-ul me-1"></i>일별 상세 내역
  </div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>날짜</th><th>시작</th><th>마감</th><th>근무</th>
          <th>휴게</th><th>유급</th><th>야간</th><th>연장</th><th>구분</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['details'] as $d): ?>
        <tr <?= $d['is_absent'] ? 'class="table-danger"' : '' ?>>
          <td><?= h($d['date']) ?>(<?= h($d['day_ko']) ?>)</td>
          <?php if ($d['is_absent']): ?>
          <td colspan="7" class="text-danger">결근</td>
          <?php else: ?>
          <td><?= h(substr($d['start'], 0, 5)) ?></td>
          <td><?= h(substr($d['end'], 0, 5)) ?></td>
          <td><?= minutesToHoursStr($d['work_min']) ?></td>
          <td class="text-muted">
            <?= minutesToHoursStr($d['break_min']) ?>
            <?= $d['break_auto'] ? '<small class="text-muted">(자동)</small>' : '' ?>
          </td>
          <td class="fw-semibold"><?= minutesToHoursStr($d['paid_min']) ?></td>
          <td class="text-primary"><?= $d['night_min'] > 0 ? minutesToHoursStr($d['night_min']) : '—' ?></td>
          <td class="text-warning-emphasis"><?= $d['daily_overtime'] > 0 ? minutesToHoursStr($d['daily_overtime']) : '—' ?></td>
          <?php endif; ?>
          <td>
            <?php if ($d['is_holiday']): ?>
              <span class="badge bg-warning-subtle text-warning border">휴일</span>
            <?php endif; ?>
            <?php if (!$d['is_absent'] && ($d['is_late'] ?? false)): ?>
              <span class="badge bg-info-subtle text-info border">지각</span>
            <?php endif; ?>
            <?php if (!$d['is_absent'] && ($d['is_employer_early_leave'] ?? false)): ?>
              <span class="badge bg-warning-subtle text-warning border">휴업</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
// 최저임금 미달 경고
$empWage    = (int)($result['employee']['hourly_wage'] ?? 0);
if ($empWage > 0 && $empWage < $periodMinWage):
?>
<div class="alert alert-danger small">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  <strong>최저임금 미달 경고:</strong>
  이 직원의 시급(<?= number_format($empWage) ?>원)이
  <?= (int)substr($periodStart, 0, 4) ?>년 법정 최저시급(<?= number_format($periodMinWage) ?>원)보다 낮습니다.
  <a href="<?= url('employees', 'edit', ['id' => $result['employee']['id']]) ?>" class="alert-link ms-1">시급 수정</a>
</div>
<?php endif; ?>

<?php if (!empty($pendingAlerts)): ?>
<div class="mb-3">
<?php foreach ($pendingAlerts as $pa): ?>
  <div class="alert alert-<?= $pa['severity'] === 'danger' ? 'danger' : 'warning' ?> d-flex align-items-start gap-2 mb-2">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
    <div>
      <strong><?= h($pa['title']) ?></strong><br>
      <span class="small"><?= h($pa['message']) ?></span>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$hasDanger = !empty($pendingAlerts) && in_array('danger', array_column($pendingAlerts, 'severity'), true);
?>

<!-- 저장 버튼 -->
<?php $insNeedsCheck = isset($insStatus) && $insStatus === 'NEEDS_CHECK'; ?>
<form method="post" action="<?= url('payroll', 'index', ['employee_id' => $employeeId, 'week_date' => $weekDate]) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="save" value="1">
  <?php if ($insNeedsCheck && empty($savedResult['id'])): ?>
  <button type="submit" id="payrollSaveBtn" class="btn btn-outline-primary me-2">
    <i class="bi bi-save me-1"></i>계산 결과 저장
  </button>
  <?php elseif ($insNeedsCheck): ?>
  <button type="button" class="btn btn-secondary me-2" disabled>
    <i class="bi bi-exclamation-triangle me-1"></i>확인 필요한 공제 항목이 있어 확정 불가
  </button>
  <?php else: ?>
  <button type="submit" id="payrollSaveBtn" class="btn btn-outline-primary me-2">
    <i class="bi bi-save me-1"></i>계산 결과 저장
  </button>
  <?php endif; ?>
  <a href="<?= url('payroll', 'monthly', ['employee_id' => $employeeId, 'year' => substr($periodStart, 0, 4), 'month' => (int)substr($periodStart, 5, 2)]) ?>"
     class="btn btn-outline-secondary">
    <i class="bi bi-bar-chart me-1"></i>이달 월간 요약 보기
  </a>
</form>

<!-- 급여명세서 발급 안내 (월 단위로 전환됨) -->
<div class="card border-0 shadow-sm mt-3">
  <div class="card-body">
    <h6 class="text-muted text-uppercase small fw-bold mb-2">
      <i class="bi bi-file-earmark-text me-1"></i>급여명세서
    </h6>
    <p class="small text-muted mb-2">
      <i class="bi bi-info-circle me-1"></i>급여명세서는 <strong>월 단위</strong>로 발급합니다.
      이 주의 계산 결과를 저장한 뒤, 해당 월의 모든 주간을 합산해 월간 급여 화면에서 발급하세요.
    </p>
    <a href="<?= url('payroll', 'monthly', ['employee_id' => $employeeId, 'year' => (int)substr($periodStart, 0, 4), 'month' => (int)substr($periodStart, 5, 2)]) ?>"
       class="btn btn-outline-success btn-sm">
      <i class="bi bi-bar-chart me-1"></i>월간 급여 화면에서 발급하기
    </a>
  </div>
</div>

<?php if ($hasDanger): ?>
<div class="modal fade" id="riskModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white py-3">
        <h6 class="modal-title fw-bold">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>노무 리스크 확인 필요
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php foreach ($pendingAlerts as $pa): if ($pa['severity'] !== 'danger') continue; ?>
        <div class="mb-3 p-3 rounded" style="background:#fff3cd;">
          <strong><?= h($pa['title']) ?></strong>
          <p class="small mb-0 mt-1"><?= h($pa['message']) ?></p>
        </div>
        <?php endforeach; ?>
        <p class="small text-muted mt-2">
          본 알림은 입력된 데이터 기준 참고용 안내입니다. 실제 법적 판단은 전문가에게 확인하세요.
        </p>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
        <form method="POST" action="<?= url('payroll', 'index', ['employee_id' => $employeeId, 'week_date' => $weekDate]) ?>" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="save" value="1">
          <input type="hidden" name="danger_acknowledged" value="1">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="bi bi-check-circle me-1"></i>리스크 확인 후 저장
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var saveBtn = document.getElementById('payrollSaveBtn');
  if (saveBtn) {
    saveBtn.addEventListener('click', function(e) {
      e.preventDefault();
      new bootstrap.Modal(document.getElementById('riskModal')).show();
    });
  }
});
</script>
<?php endif; ?>

<?php endif; ?>

<!-- 법적 안내 -->
<div class="alert alert-warning mt-4 small">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  본 계산 결과는 입력된 근무시간·시급·사업장 설정을 기준으로 한 <strong>참고용 예상 금액</strong>입니다.
  실제 임금 지급 여부와 금액은 근로계약서·소정근로시간·소정근로일·결근 여부·
  사업장 상시근로자 수·최신 근로기준법 및 최저임금 고시에 따라 달라질 수 있습니다.
</div>

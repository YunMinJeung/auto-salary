<?php
$emp  = $data['employee'];
$m    = $data['monthly'];
$periodLabel = "{$year}년 {$month}월 1일 ~ " . date('Y년 m월 d일', strtotime("{$year}-{$month}-01 last day of this month"));
$issueDate   = date('Y년 m월 d일');
$bizName     = h($store['store_name'] ?? $settings['business_name'] ?? '사업장');
?>

<!-- ── 이동 버튼 ──────────────────────────────────── -->
<div class="no-print text-end mb-3">
  <a href="<?= url('payroll', 'monthly', ['employee_id' => $emp['id'] ?? 0, 'year' => $year, 'month' => $month]) ?>"
     class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>월간 요약으로
  </a>
</div>

<!-- ── 헤더 ───────────────────────────────────────── -->
<div class="slip-head d-flex justify-content-between align-items-end">
  <div>
    <div class="slip-title">급 여 명 세 서</div>
    <div class="text-muted small mt-1"><?= $bizName ?></div>
  </div>
  <div class="text-end small text-muted">
    <div>발급일: <?= $issueDate ?></div>
    <div>급여기간: <?= $year ?>년 <?= $month ?>월</div>
  </div>
</div>

<!-- ── 직원 정보 ──────────────────────────────────── -->
<table class="slip-table mb-4">
  <tbody>
    <tr>
      <th style="width:130px">성명</th>
      <td class="fw-bold"><?= h($emp['name']) ?></td>
      <th style="width:130px">시급</th>
      <td><?= number_format($emp['hourly_wage']) ?>원</td>
    </tr>
    <tr>
      <th>급여기간</th>
      <td colspan="3"><?= $periodLabel ?></td>
    </tr>
  </tbody>
</table>

<div class="row g-4 mb-4">

  <!-- ── 지급 내역 ──────────────────────────────── -->
  <div class="col-md-6">
    <h6 class="fw-bold mb-2" style="color:var(--c-dark)">지 급 내 역</h6>
    <table class="slip-table">
      <thead>
        <tr>
          <th>항목</th>
          <th class="text-end" style="width:80px">시간</th>
          <th class="text-end" style="width:100px">금액</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>기본급</td>
          <td class="text-end text-muted small"><?= minutesToHoursStr($m['paid_work_minutes']) ?></td>
          <td class="text-end"><?= formatWon($m['base_pay']) ?></td>
        </tr>
        <?php if ($m['weekly_holiday_pay'] > 0): ?>
        <tr>
          <td>주휴수당</td>
          <td class="text-end text-muted small">
            <?= number_format(array_sum(array_column($data['weeks'], 'weekly_holiday_hours')), 1) ?>시간
          </td>
          <td class="text-end text-success"><?= formatWon($m['weekly_holiday_pay']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($m['night_premium'] > 0): ?>
        <tr>
          <td>야간 가산수당</td>
          <td class="text-end text-muted small"><?= minutesToHoursStr($m['night_minutes']) ?></td>
          <td class="text-end"><?= formatWon($m['night_premium']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($m['overtime_premium'] > 0): ?>
        <tr>
          <td>연장 가산수당</td>
          <td class="text-end text-muted small"><?= minutesToHoursStr($m['overtime_minutes']) ?></td>
          <td class="text-end"><?= formatWon($m['overtime_premium']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (isset($m['holiday_premium']) && $m['holiday_premium'] > 0): ?>
        <tr>
          <td>휴일 가산수당</td>
          <td class="text-end text-muted small">—</td>
          <td class="text-end"><?= formatWon($m['holiday_premium']) ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2">지급 합계</td>
          <td class="text-end fs-6"><?= formatWon($grossPay) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- ── 공제 내역 ──────────────────────────────── -->
  <div class="col-md-6">
    <h6 class="fw-bold mb-2" style="color:var(--c-dark)">공 제 내 역</h6>
    <table class="slip-table">
      <thead>
        <tr>
          <th>항목</th>
          <th class="text-end" style="width:60px">요율</th>
          <th class="text-end" style="width:100px">금액</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($deductions['national_pension'] > 0): ?>
        <tr>
          <td>국민연금</td>
          <td class="text-end text-muted small">4.75%</td>
          <td class="text-end"><?= formatWon($deductions['national_pension']) ?></td>
        </tr>
        <?php elseif (!($settings['apply_national_pension'] ?? 1)): ?>
        <tr class="text-muted">
          <td>국민연금</td>
          <td class="text-end small">미적용</td>
          <td class="text-end">—</td>
        </tr>
        <?php endif; ?>

        <?php if ($deductions['health_insurance'] > 0): ?>
        <tr>
          <td>건강보험</td>
          <td class="text-end text-muted small">3.595%</td>
          <td class="text-end"><?= formatWon($deductions['health_insurance']) ?></td>
        </tr>
        <tr>
          <td>장기요양보험</td>
          <td class="text-end text-muted small">13.14%<span class="d-none d-print-none">*</span></td>
          <td class="text-end"><?= formatWon($deductions['long_term_care']) ?></td>
        </tr>
        <?php elseif (!($settings['apply_health_insurance'] ?? 1)): ?>
        <tr class="text-muted">
          <td>건강보험 / 장기요양</td>
          <td class="text-end small">미적용</td>
          <td class="text-end">—</td>
        </tr>
        <?php endif; ?>

        <?php if ($deductions['employment_insurance'] > 0): ?>
        <tr>
          <td>고용보험</td>
          <td class="text-end text-muted small">0.9%</td>
          <td class="text-end"><?= formatWon($deductions['employment_insurance']) ?></td>
        </tr>
        <?php elseif (!($settings['apply_employment_insurance'] ?? 1)): ?>
        <tr class="text-muted">
          <td>고용보험</td>
          <td class="text-end small">미적용</td>
          <td class="text-end">—</td>
        </tr>
        <?php endif; ?>

        <tr class="text-muted">
          <td>산재보험</td>
          <td class="text-end small">—</td>
          <td class="text-end">사용자 부담</td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2">공제 합계</td>
          <td class="text-end fs-6"><?= formatWon($deductions['total']) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

</div>

<!-- ── 실수령액 ───────────────────────────────────── -->
<div class="text-end mb-4 p-3 rounded" style="background:var(--c-cream);border-left:4px solid var(--c-dark)">
  <div class="small text-muted mb-1">실 수 령 액</div>
  <div class="fw-bold" style="font-size:2rem;color:var(--c-dark)"><?= formatWon($netPay) ?></div>
  <div class="small text-muted">
    지급 합계 <?= formatWon($grossPay) ?> − 공제 합계 <?= formatWon($deductions['total']) ?>
  </div>
</div>

<!-- ── 주별 근무 내역 ─────────────────────────────── -->
<details class="mb-4 no-print" open>
  <summary class="fw-semibold mb-2" style="cursor:pointer;color:var(--c-dark)">
    <i class="bi bi-list-ul me-1"></i>주별 근무 상세
  </summary>
  <table class="slip-table small mt-2">
    <thead>
      <tr>
        <th>기간</th>
        <th class="text-end">유급근무</th>
        <th class="text-end">야간</th>
        <th class="text-end">연장</th>
        <th class="text-end">기본급</th>
        <th class="text-end">주휴수당</th>
        <th class="text-end fw-bold">소계</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data['weeks'] as $w): ?>
      <tr>
        <td class="text-nowrap"><?= h($w['period_start']) ?> ~ <?= h($w['period_end']) ?></td>
        <td class="text-end"><?= minutesToHoursStr($w['paid_work_minutes']) ?></td>
        <td class="text-end"><?= $w['night_minutes'] > 0 ? minutesToHoursStr($w['night_minutes']) : '—' ?></td>
        <td class="text-end"><?= $w['overtime_minutes'] > 0 ? minutesToHoursStr($w['overtime_minutes']) : '—' ?></td>
        <td class="text-end"><?= formatWon($w['base_pay']) ?></td>
        <td class="text-end text-success"><?= $w['weekly_holiday_pay'] > 0 ? formatWon($w['weekly_holiday_pay']) : '—' ?></td>
        <td class="text-end fw-bold"><?= formatWon($w['total_pay']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td>합 계</td>
        <td class="text-end"><?= minutesToHoursStr($m['paid_work_minutes']) ?></td>
        <td class="text-end"><?= $m['night_minutes'] > 0 ? minutesToHoursStr($m['night_minutes']) : '—' ?></td>
        <td class="text-end"><?= $m['overtime_minutes'] > 0 ? minutesToHoursStr($m['overtime_minutes']) : '—' ?></td>
        <td class="text-end"><?= formatWon($m['base_pay']) ?></td>
        <td class="text-end"><?= formatWon($m['weekly_holiday_pay']) ?></td>
        <td class="text-end"><?= formatWon($m['total_pay']) ?></td>
      </tr>
    </tfoot>
  </table>
</details>

<!-- ── 인쇄용 주별 내역 (always show on print) ──── -->
<div style="display:none" class="d-print-block mb-4">
  <table class="slip-table small">
    <thead>
      <tr>
        <th>기간</th>
        <th class="text-end">유급근무</th>
        <th class="text-end">기본급</th>
        <th class="text-end">주휴수당</th>
        <th class="text-end fw-bold">소계</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data['weeks'] as $w): ?>
      <tr>
        <td><?= h(substr($w['period_start'], 5)) ?> ~ <?= h(substr($w['period_end'], 5)) ?></td>
        <td class="text-end"><?= minutesToHoursStr($w['paid_work_minutes']) ?></td>
        <td class="text-end"><?= formatWon($w['base_pay']) ?></td>
        <td class="text-end"><?= $w['weekly_holiday_pay'] > 0 ? formatWon($w['weekly_holiday_pay']) : '—' ?></td>
        <td class="text-end fw-bold"><?= formatWon($w['total_pay']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ── 4대보험 가입 의무 체크 참고 ───────────────────── -->
<?php if (!empty($insCheck)): ?>
<div class="no-print mb-4 p-3 rounded border" style="border-color:#ffc107!important;background:#fffdf0">
  <div class="fw-semibold small mb-2" style="color:var(--c-dark)">
    <i class="bi bi-shield-check me-1" style="color:var(--c-amber)"></i>4대보험 가입 의무 체크
    <span class="fw-normal text-muted">(주 <?= number_format($insCheck['weekly_hours'], 1) ?>시간 기준 / 근무기간 미확인)</span>
  </div>
  <div class="row g-2 small">
    <?php
    $insRows = [
      'national_pension'     => '국민연금',
      'health_insurance'     => '건강보험 / 장기요양',
      'employment_insurance' => '고용보험',
      'industrial_accident'  => '산재보험',
    ];
    $insLabels = [
      'likely_required' => ['text-danger',    '가입 대상 가능성 높음'],
      'possibly_exempt' => ['text-secondary', '제외 가능성 있음'],
      'needs_review'    => ['text-warning',   '확인 필요'],
      'required'        => ['text-info',      '사용자 전액 부담'],
    ];
    foreach ($insRows as $key => $name):
      $status = $insCheck[$key] ?? 'needs_review';
      [$cls, $lbl] = $insLabels[$status] ?? ['text-muted', $status];
    ?>
    <div class="col-sm-6 col-md-3">
      <div class="px-2 py-1 rounded bg-white border d-flex justify-content-between">
        <span class="text-muted"><?= $name ?></span>
        <strong class="<?= $cls ?>"><?= $lbl ?></strong>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="text-muted mt-2" style="font-size:.75rem">
    ※ 정확한 가입 의무는 근무기간·고용 형태 등에 따라 달라집니다.
    직원 계정 관리 → 직원 수정에서 상세 설정하세요.
  </div>
</div>
<?php endif; ?>

<!-- ── 서명란 ─────────────────────────────────────── -->
<div class="sign-box row">
  <div class="col-6 text-center">
    <div class="small text-muted mb-1">사업주</div>
    <div class="sign-line"></div>
    <div class="small text-muted mt-1"><?= $bizName ?></div>
  </div>
  <div class="col-6 text-center">
    <div class="small text-muted mb-1">근로자 (수령 확인)</div>
    <div class="sign-line"></div>
    <div class="small text-muted mt-1"><?= h($emp['name']) ?></div>
  </div>
</div>

<!-- ── 하단 주석 ──────────────────────────────────── -->
<div class="notice">
  <i class="bi bi-info-circle me-1"></i>
  본 급여명세서는 근로기준법 제48조에 따라 발급됩니다.
  4대보험 요율은 2026년 기준(국민연금 4.75% · 건강보험 3.595% · 장기요양보험 건강보험료의 13.14% · 고용보험 0.9%)이며,
  매년 변경될 수 있습니다. 실제 납부액은 관련 기관 확인 후 지급하세요.
</div>

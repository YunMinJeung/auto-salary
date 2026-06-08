<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-cash-coin me-2 text-success"></i>주간 급여 계산</h1>
</div>

<!-- 조회 폼 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" action="<?= url('payroll') ?>" class="row g-3 align-items-end">
      <input type="hidden" name="c" value="payroll">
      <div class="col-12 col-sm-5 col-md-4">
        <label class="form-label fw-semibold">직원</label>
        <select name="employee_id" class="form-select" required>
          <option value="">— 선택 —</option>
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

<?php if ($result): ?>
<?php $emp = $result['employee']; ?>

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
          ['label' => '총 근무시간',   'min' => $result['total_work_minutes'],  'class' => ''],
          ['label' => '휴게시간',       'min' => $result['break_minutes'],       'class' => 'text-muted'],
          ['label' => '유급 근무시간', 'min' => $result['paid_work_minutes'],   'class' => 'fw-bold'],
          ['label' => '야간근로',       'min' => $result['night_minutes'],       'class' => 'text-primary'],
          ['label' => '연장근로',       'min' => $result['overtime_minutes'],    'class' => 'text-warning-emphasis'],
          ['label' => '휴일근로',       'min' => $result['holiday_minutes'],     'class' => 'text-danger'],
      ];
      foreach ($timeItems as $item):
      ?>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="border rounded p-2 text-center h-100">
          <div class="small text-muted"><?= $item['label'] ?></div>
          <div class="fw-semibold <?= $item['class'] ?>">
            <?= minutesToHoursStr($item['min']) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- 급여 내역 -->
    <h6 class="text-muted text-uppercase small fw-bold mb-3">급여 내역</h6>
    <table class="table table-sm mb-4" style="max-width:500px">
      <tbody>
        <tr>
          <td>기본급</td>
          <td class="text-end">
            <?= minutesToHoursStr($result['paid_work_minutes']) ?> × <?= number_format($emp['hourly_wage']) ?>원
          </td>
          <td class="text-end fw-semibold"><?= formatWon($result['base_pay']) ?></td>
        </tr>

        <?php if ($result['holiday_enabled']): ?>
        <tr class="<?= $result['weekly_holiday_hours'] > 0 ? '' : 'text-muted' ?>">
          <td>
            주휴수당
            <i class="bi bi-question-circle small" data-bs-toggle="tooltip"
               title="1주 소정근로시간 15시간 이상 + 개근 시 발생하는 유급휴일 수당"></i>
          </td>
          <td class="text-end">
            <?php if ($result['weekly_holiday_hours'] > 0): ?>
              <?= number_format($result['weekly_holiday_hours'], 2) ?>시간 × <?= number_format($emp['hourly_wage']) ?>원
            <?php else: ?>
              미발생
            <?php endif; ?>
          </td>
          <td class="text-end fw-semibold">
            <?= $result['weekly_holiday_hours'] > 0 ? formatWon($result['weekly_holiday_pay']) : '—' ?>
          </td>
        </tr>
        <?php endif; ?>

        <?php if ($settings['apply_night_premium']): ?>
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

        <?php if ($settings['apply_overtime_premium']): ?>
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

        <?php if ($settings['apply_holiday_premium']): ?>
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

        <tr class="table-success fw-bold">
          <td colspan="2">총 지급 예상액</td>
          <td class="text-end fs-5"><?= formatWon($result['total_pay']) ?></td>
        </tr>
      </tbody>
    </table>

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
          <th>날짜</th>
          <th>시작</th>
          <th>마감</th>
          <th>근무</th>
          <th>휴게</th>
          <th>유급</th>
          <th>야간</th>
          <th>연장</th>
          <th>구분</th>
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
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 저장 버튼 -->
<form method="post" action="<?= url('payroll', 'index', ['employee_id' => $employeeId, 'week_date' => $weekDate]) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="save" value="1">
  <button type="submit" class="btn btn-outline-primary me-2">
    <i class="bi bi-save me-1"></i>계산 결과 저장
  </button>
  <a href="<?= url('payroll', 'monthly', ['employee_id' => $employeeId, 'year' => substr($periodStart, 0, 4), 'month' => (int)substr($periodStart, 5, 2)]) ?>"
     class="btn btn-outline-secondary">
    <i class="bi bi-bar-chart me-1"></i>이달 월간 요약 보기
  </a>
</form>

<?php
// 최저임금 미달 경고
$empWage    = (int)($result['employee']['hourly_wage'] ?? 0);
$minWageYear = (int) substr($periodStart, 0, 4);
if ($empWage > 0 && $empWage < $periodMinWage):
?>
<div class="alert alert-danger mt-4 small">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  <strong>최저임금 미달 경고:</strong>
  이 직원의 시급(<?= number_format($empWage) ?>원)이
  <?= $minWageYear ?>년 법정 최저시급(<?= number_format($periodMinWage) ?>원)보다 낮습니다.
  <a href="<?= url('employees', 'edit', ['id' => $result['employee']['id']]) ?>" class="alert-link ms-1">시급 수정</a>
</div>
<?php endif; ?>

<!-- 법적 안내 -->
<div class="alert alert-warning mt-4 small">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  본 계산 결과는 입력된 근무시간·시급·사업장 설정을 기준으로 한 <strong>참고용 예상 금액</strong>입니다.
  실제 임금 지급 여부와 금액은 근로계약서·소정근로시간·소정근로일·결근 여부·사업장 상시근로자 수·
  최신 근로기준법 및 최저임금 고시에 따라 달라질 수 있습니다.
</div>

<?php elseif ($employeeId): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-1"></i>
  해당 기간(<?= h($periodStart) ?> ~ <?= h($periodEnd) ?>)에 근무 기록이 없습니다.
  <a href="<?= url('work_logs', 'create', ['employee_id' => $employeeId]) ?>" class="alert-link">근무 기록 추가</a>
</div>
<?php endif; ?>

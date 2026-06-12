<div class="d-flex align-items-center mb-4">
  <a href="<?= url('members', 'add') ?>" class="btn btn-sm btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="h3 mb-0"><i class="bi bi-person-badge-fill me-2 text-info"></i>계정 ID로 연결</h1>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger small py-2 mb-4" style="max-width:600px">
  <?php foreach ($errors as $e): ?><div><i class="bi bi-exclamation-circle me-1"></i><?= h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:600px">

<?php if (!$found): ?>
<!-- ── 검색 폼 ── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-4">
    <p class="text-muted small mb-3">
      알바생이 페이클락에 가입한 계정의 <strong>사용자 ID(숫자)</strong> 또는 <strong>이메일</strong>을 입력하세요.
    </p>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="search">
      <div class="input-group">
        <input type="text" name="query" class="form-control"
               placeholder="예: 42  또는  alba01@test.com"
               value="<?= h($old['query'] ?? '') ?>" autofocus>
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-search me-1"></i>검색
        </button>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ── 검색 결과 + 근로조건 입력 ── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold small py-2">
    <i class="bi bi-person-check me-1 text-success"></i>계정 확인됨
  </div>
  <div class="card-body p-4">
    <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:var(--c-bg)">
      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
           style="width:44px;height:44px;font-size:1.1rem;background:var(--primary);flex-shrink:0">
        <?= h(mb_substr($found['name'], 0, 1)) ?>
      </div>
      <div>
        <div class="fw-semibold"><?= h($found['name']) ?></div>
        <div class="text-muted small"><?= h($found['email']) ?> &nbsp;·&nbsp; ID: <?= (int)$found['id'] ?></div>
      </div>
    </div>

    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="link">
      <input type="hidden" name="user_id" value="<?= (int)$found['id'] ?>">

      <div class="mb-3">
        <label class="form-label fw-semibold">매장에서 표시할 이름 <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control"
               value="<?= h($old['name'] ?? $found['name']) ?>" required>
      </div>

      <!-- 시급 | 주 근로시간 | 주 근로일 | 휴게시간 -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <label class="form-label fw-semibold">시급 <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" id="hourlyWage" name="hourly_wage" class="form-control"
                   value="<?= h($old['hourly_wage'] ?? '') ?>" min="1" required>
            <span class="input-group-text">원</span>
          </div>
          <div id="minWageWarning" class="text-danger small mt-1" style="display:none">
            <i class="bi bi-exclamation-circle-fill"></i> 2026년 최저임금(10,320원) 미만
          </div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label fw-semibold">주 근로시간</label>
          <div class="input-group">
            <input type="number" id="contractHours" name="weekly_contract_hours" class="form-control"
                   value="<?= h($old['weekly_contract_hours'] ?? 40) ?>" min="0" max="80" step="any">
            <span class="input-group-text">h</span>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label fw-semibold">주 근로일</label>
          <div class="input-group">
            <input type="number" id="contractDays" name="weekly_contract_days" class="form-control"
                   value="<?= h($old['weekly_contract_days'] ?? 5) ?>" min="1" max="7">
            <span class="input-group-text">일</span>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label fw-semibold">일 휴게시간</label>
          <div class="input-group">
            <input type="number" id="breakMinutes" name="daily_break_minutes" class="form-control"
                   value="<?= h($old['daily_break_minutes'] ?? 60) ?>" min="0" max="480">
            <span class="input-group-text">분</span>
          </div>
          <div id="breakWarning" class="text-danger small mt-1" style="display:none"></div>
        </div>
      </div>

      <!-- 입사일 | 주휴수당 -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label fw-semibold">입사일</label>
          <input type="date" name="hire_date" class="form-control"
                 value="<?= h($old['hire_date'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold d-block">&nbsp;</label>
          <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" id="weeklyHolidayPay"
                   name="weekly_holiday_pay_enabled" value="1" checked>
            <label class="form-check-label" for="weeklyHolidayPay">주휴수당 계산 대상</label>
          </div>
          <div id="holidayPayWarning" class="text-danger small mt-1" style="display:none">
            <i class="bi bi-exclamation-triangle-fill"></i>
            주 15시간 이상 근무자는 주휴수당 지급 의무가 있습니다 (근로기준법 제18조)
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-person-badge-fill me-1"></i>연결 완료
        </button>
        <a href="<?= url('members', 'link_account') ?>" class="btn btn-outline-secondary">다시 검색</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

</div>

<script>
(function () {
  var MIN_WAGE = 10320;

  var wageInput  = document.getElementById('hourlyWage');
  var hoursInput = document.getElementById('contractHours');
  var daysInput  = document.getElementById('contractDays');
  var breakInput = document.getElementById('breakMinutes');
  var checkbox   = document.getElementById('weeklyHolidayPay');
  var holidayWarn = document.getElementById('holidayPayWarning');
  var minWageWarn = document.getElementById('minWageWarning');
  var breakWarn   = document.getElementById('breakWarning');

  if (!checkbox) return;
  var userOverride = false;

  function checkMinWage() {
    if (!wageInput || !minWageWarn) return;
    var w = parseInt(wageInput.value) || 0;
    minWageWarn.style.display = (w > 0 && w < MIN_WAGE) ? 'block' : 'none';
  }

  function checkBreak() {
    if (!hoursInput || !daysInput || !breakInput || !breakWarn) return;
    var weekly = parseFloat(hoursInput.value) || 0;
    var days   = Math.max(parseInt(daysInput.value) || 1, 1);
    var brk    = parseInt(breakInput.value) || 0;
    var dailyH = weekly / days;
    var need   = dailyH >= 8 ? 60 : (dailyH >= 4 ? 30 : 0);
    if (need > 0 && brk < need) {
      breakWarn.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> 1일 ' + dailyH.toFixed(1) + 'h 근무 시 ' + need + '분 이상 휴게 필요 (근로기준법 제54조)';
      breakWarn.style.display = 'block';
    } else {
      breakWarn.style.display = 'none';
    }
  }

  function syncCheckbox() {
    if (userOverride) return;
    checkbox.checked = (parseFloat(hoursInput.value) || 0) >= 15;
    holidayWarn.style.display = 'none';
  }

  if (wageInput)  wageInput.addEventListener('input', checkMinWage);
  if (hoursInput) hoursInput.addEventListener('input', function () { userOverride = false; syncCheckbox(); checkBreak(); });
  if (daysInput)  daysInput.addEventListener('input', checkBreak);
  if (breakInput) breakInput.addEventListener('input', checkBreak);

  checkbox.addEventListener('change', function () {
    var h = parseFloat(hoursInput.value) || 0;
    if (!checkbox.checked && h >= 15) { holidayWarn.style.display = 'block'; userOverride = true; }
    else { holidayWarn.style.display = 'none'; userOverride = false; }
  });

  checkMinWage();
  syncCheckbox();
  checkBreak();
}());
</script>

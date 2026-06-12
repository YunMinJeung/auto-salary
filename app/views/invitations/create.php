<?php $isGuided = !empty($_GET['guided']); ?>
<?php $member = $member ?? null; ?>

<div class="d-flex align-items-center mb-4">
  <a href="<?= url('members', 'add') ?>" class="btn btn-sm btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="h3 mb-0">
    <i class="bi bi-<?= $isGuided ? 'qr-code' : 'link-45deg' ?> me-2 text-<?= $isGuided ? 'warning' : 'success' ?>"></i>
    <?= $isGuided ? '가입 도와주기' : '초대 링크로 추가' ?>
  </h1>
</div>

<?php if ($isGuided): ?>
<div class="alert alert-warning small mb-4" style="max-width:640px">
  <i class="bi bi-exclamation-triangle me-1"></i>
  <strong>안내:</strong> 직원 본인이 직접 이름·약관 동의·비밀번호를 설정해야 합니다.
  사장님은 직원 화면을 열어주기만 하세요.
</div>
<?php endif; ?>

<div style="max-width:640px">
<form method="POST" action="<?= url('invite', 'create') ?><?= $isGuided ? '?guided=1' : '' ?>">
  <?= csrf_field() ?>
  <?php if ($member): ?>
  <input type="hidden" name="store_member_id" value="<?= (int)$member['id'] ?>">
  <div class="alert alert-secondary small py-2 mb-4">
    기존 직원 카드 <strong><?= h($member['name']) ?></strong>에 연결됩니다.
    근로조건은 등록된 정보로 자동 입력됩니다.
  </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold small py-2">
      <i class="bi bi-person me-1"></i>직원 기본 정보
    </div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-semibold">직원명 또는 별칭 <span class="text-danger">*</span></label>
        <input type="text" name="invited_name" class="form-control"
               value="<?= h($member['name'] ?? '') ?>" required>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">휴대폰 번호 <span class="text-muted fw-normal small">(선택)</span></label>
          <input type="tel" name="invited_phone" class="form-control"
                 value="<?= h($member['phone'] ?? '') ?>" placeholder="010-0000-0000">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">이메일 <span class="text-muted fw-normal small">(선택)</span></label>
          <input type="email" name="invited_email" class="form-control"
                 value="<?= h($member['user_email'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold small py-2">
      <i class="bi bi-currency-dollar me-1"></i>근로조건 <span class="text-muted fw-normal">(초대 확인 화면에 표시됩니다)</span>
    </div>
    <div class="card-body">
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">시급 <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" name="hourly_wage" class="form-control"
                   value="<?= h($member['hourly_wage'] ?? '') ?>" min="1" required>
            <span class="input-group-text">원</span>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">주 소정근로시간</label>
          <div class="input-group">
            <input type="number" name="weekly_contract_hours" class="form-control"
                   value="<?= h($member['weekly_scheduled_hours'] ?? 40) ?>" min="0" max="80" step="any">
            <span class="input-group-text">h</span>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">주 소정근로일</label>
          <div class="input-group">
            <input type="number" name="weekly_contract_days" class="form-control"
                   value="<?= h($member['weekly_scheduled_days'] ?? 5) ?>" min="1" max="7">
            <span class="input-group-text">일</span>
          </div>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">입사일</label>
          <input type="date" name="hire_date" class="form-control"
                 value="<?= h($member['employment_start_date'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-6 d-flex align-items-end pb-1">
          <div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="weeklyHolidayPay" name="weekly_holiday_pay_enabled" value="1"
                     <?= ($member['weekly_holiday_enabled'] ?? 1) ? 'checked' : '' ?>>
              <label class="form-check-label" for="weeklyHolidayPay">주휴수당 계산 대상</label>
            </div>
            <div id="holidayPayWarning" class="text-danger small mt-1" style="display:none">
              <i class="bi bi-exclamation-triangle-fill"></i>
              주 15시간 이상 근무자는 주휴수당 지급 의무가 있습니다 (근로기준법 제18조)
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-info small mb-4">
    <i class="bi bi-shield-check me-1"></i>
    이 계정은 근로자 본인이 소유하는 개인 계정입니다.
    사장님은 해당 매장의 근무기록과 급여 계산에 필요한 정보만 확인할 수 있습니다.
    다른 매장의 근무 정보는 공개되지 않습니다.
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">
      <i class="bi bi-link-45deg me-1"></i>초대 링크 생성
    </button>
    <a href="<?= url('members') ?>" class="btn btn-outline-secondary">취소</a>
  </div>
</form>
</div>
<script>
(function () {
  var hoursInput   = document.querySelector('input[name="weekly_contract_hours"]');
  var checkbox     = document.getElementById('weeklyHolidayPay');
  var warning      = document.getElementById('holidayPayWarning');
  var THRESHOLD    = 15;
  var userOverride = false;

  function syncCheckbox() {
    if (userOverride) return;
    var h = parseFloat(hoursInput.value) || 0;
    checkbox.checked = h >= THRESHOLD;
    warning.style.display = 'none';
  }

  hoursInput.addEventListener('input', function () {
    userOverride = false;
    syncCheckbox();
  });

  checkbox.addEventListener('change', function () {
    var h = parseFloat(hoursInput.value) || 0;
    if (!checkbox.checked && h >= THRESHOLD) {
      warning.style.display = 'block';
      userOverride = true;
    } else {
      warning.style.display = 'none';
      userOverride = !!checkbox.checked;
    }
  });

  // 초기 실행 (기존 직원 카드 없을 때 시간 기반 초기화)
  <?php if (!$member): ?>
  syncCheckbox();
  <?php endif; ?>
}());
</script>

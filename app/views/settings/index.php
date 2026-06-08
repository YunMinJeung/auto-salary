<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-gear-fill me-2 text-secondary"></i>사업장 설정</h1>
</div>

<div class="card border-0 shadow-sm" style="max-width:640px">
  <div class="card-body">
    <form method="post" action="<?= url('settings') ?>">
      <?= csrf_field() ?>

      <h6 class="text-muted mb-3 text-uppercase small fw-bold">기본 정보</h6>

      <div class="mb-3">
        <label class="form-label fw-semibold">사업장명</label>
        <input type="text" name="business_name" class="form-control"
               value="<?= h($settings['business_name']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">
          상시근로자 수
          <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
             title="5인 이상 사업장은 연장·야간·휴일 가산수당이 법적으로 의무 적용됩니다."></i>
        </label>
        <div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="employee_count_type"
                   id="et_over5" value="over5"
                   <?= $settings['employee_count_type'] === 'over5' ? 'checked' : '' ?>>
            <label class="form-check-label" for="et_over5">5인 이상</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="employee_count_type"
                   id="et_under5" value="under5"
                   <?= $settings['employee_count_type'] === 'under5' ? 'checked' : '' ?>>
            <label class="form-check-label" for="et_under5">5인 미만</label>
          </div>
        </div>
      </div>

      <hr>
      <h6 class="text-muted mb-3 text-uppercase small fw-bold">최저임금</h6>

      <div class="row g-3 mb-3">
        <div class="col-4">
          <label class="form-label fw-semibold">기준 연도</label>
          <input type="number" name="minimum_wage_year" class="form-control"
                 value="<?= h($settings['minimum_wage_year']) ?>" min="2020" max="2100">
        </div>
        <div class="col-8">
          <label class="form-label fw-semibold">최저시급 (원)</label>
          <div class="input-group">
            <input type="number" name="minimum_wage" class="form-control"
                   value="<?= h($settings['minimum_wage']) ?>" min="1">
            <span class="input-group-text">원</span>
          </div>
          <div class="form-text">2026년: 10,320원</div>
        </div>
      </div>

      <hr>
      <h6 class="text-muted mb-3 text-uppercase small fw-bold">가산수당 적용</h6>
      <div class="alert alert-info small py-2 mb-3">
        5인 이상 사업장은 연장·야간·휴일 가산수당이 의무입니다.
        5인 미만 사업장도 체크하여 계산에 포함할 수 있습니다.
      </div>

      <div class="mb-2">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="apply_overtime_premium"
                 name="apply_overtime_premium" value="1"
                 <?= $settings['apply_overtime_premium'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="apply_overtime_premium">
            연장근로 가산수당 적용 <span class="text-muted small">(50% 가산)</span>
          </label>
        </div>
      </div>
      <div class="mb-2">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="apply_night_premium"
                 name="apply_night_premium" value="1"
                 <?= $settings['apply_night_premium'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="apply_night_premium">
            야간근로 가산수당 적용 <span class="text-muted small">(22:00~06:00, 50% 가산)</span>
          </label>
        </div>
      </div>
      <div class="mb-2">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="apply_holiday_premium"
                 name="apply_holiday_premium" value="1"
                 <?= $settings['apply_holiday_premium'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="apply_holiday_premium">
            휴일근로 가산수당 적용 <span class="text-muted small">(50% 가산)</span>
          </label>
        </div>
      </div>

      <hr>
      <h6 class="text-muted mb-3 text-uppercase small fw-bold">자동 계산</h6>

      <div class="mb-2">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="auto_break_enabled"
                 name="auto_break_enabled" value="1"
                 <?= $settings['auto_break_enabled'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="auto_break_enabled">
            휴게시간 자동 계산
            <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
               title="4시간 이상: 30분, 8시간 이상: 60분 자동 차감"></i>
          </label>
        </div>
      </div>
      <div class="mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="auto_weekly_holiday_enabled"
                 name="auto_weekly_holiday_enabled" value="1"
                 <?= $settings['auto_weekly_holiday_enabled'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="auto_weekly_holiday_enabled">
            주휴수당 자동 계산
            <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
               title="1주 소정근로시간 15시간 이상 + 개근 시 자동 계산"></i>
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-save me-1"></i>저장
      </button>
    </form>
  </div>
</div>

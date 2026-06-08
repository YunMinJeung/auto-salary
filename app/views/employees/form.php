<?php $isEdit = $action === 'edit'; ?>
<div class="d-flex align-items-center mb-4">
  <a href="<?= url('employees') ?>" class="btn btn-sm btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="h3 mb-0">
    <i class="bi bi-person-plus-fill me-2 text-primary"></i>
    <?= $isEdit ? '직원 수정' : '직원 등록' ?>
  </h1>
</div>

<div class="card border-0 shadow-sm" style="max-width:640px">
  <div class="card-body">
    <form method="post"
          action="<?= $isEdit ? url('employees', 'edit', ['id' => $employee['id']]) : url('employees', 'create') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">이름 <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
               value="<?= h($employee['name'] ?? '') ?>" required>
        <?php if (isset($errors['name'])): ?>
          <div class="invalid-feedback"><?= h($errors['name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">
          시급 <span class="text-danger">*</span>
          <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
             title="시간당 임금입니다. 2026년 최저시급 10,320원 이상이어야 합니다."></i>
        </label>
        <div class="input-group">
          <input type="number" name="hourly_wage"
                 class="form-control <?= isset($errors['hourly_wage']) ? 'is-invalid' : '' ?>"
                 value="<?= h($employee['hourly_wage'] ?? $settings['minimum_wage']) ?>"
                 min="1" required>
          <span class="input-group-text">원/시간</span>
          <?php if (isset($errors['hourly_wage'])): ?>
            <div class="invalid-feedback"><?= h($errors['hourly_wage']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-text">최저시급 기준: <?= number_format($settings['minimum_wage']) ?>원</div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">
            주 소정근로시간
            <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
               title="근로계약서상 일하기로 정한 주당 시간. 주휴수당 계산 기준이 됩니다."></i>
          </label>
          <div class="input-group">
            <input type="number" name="weekly_scheduled_hours" class="form-control"
                   value="<?= h($employee['weekly_scheduled_hours'] ?? 40) ?>"
                   min="0" max="80" step="0.5">
            <span class="input-group-text">시간</span>
          </div>
          <div class="form-text">15시간 이상이면 주휴수당 대상</div>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">
            주 소정근로일
            <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
               title="근로계약서상 일하기로 정한 주당 일수. 개근 여부 판단에 사용됩니다."></i>
          </label>
          <div class="input-group">
            <input type="number" name="weekly_scheduled_days" class="form-control"
                   value="<?= h($employee['weekly_scheduled_days'] ?? 5) ?>"
                   min="1" max="7">
            <span class="input-group-text">일</span>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="weekly_holiday_enabled"
                 name="weekly_holiday_enabled" value="1"
                 <?= ($employee['weekly_holiday_enabled'] ?? 1) ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold" for="weekly_holiday_enabled">
            주휴수당 계산 대상
            <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
               title="1주 15시간 이상 일하기로 하고, 정해진 근무일에 개근한 경우 발생할 수 있는 유급휴일수당"></i>
          </label>
        </div>
      </div>

      <hr>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">입사일 <span class="text-danger">*</span></label>
          <input type="date" name="employment_start_date"
                 class="form-control <?= isset($errors['employment_start_date']) ? 'is-invalid' : '' ?>"
                 value="<?= h($employee['employment_start_date'] ?? '') ?>" required>
          <?php if (isset($errors['employment_start_date'])): ?>
            <div class="invalid-feedback"><?= h($errors['employment_start_date']) ?></div>
          <?php endif; ?>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">퇴사일 <span class="text-muted small">(선택)</span></label>
          <input type="date" name="employment_end_date" class="form-control"
                 value="<?= h($employee['employment_end_date'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">메모</label>
        <textarea name="memo" class="form-control" rows="2"><?= h($employee['memo'] ?? '') ?></textarea>
      </div>

      <hr>

      <div class="mb-3">
        <h6 class="fw-semibold mb-1">가산수당 개별 설정</h6>
        <p class="text-muted small mb-3">
          5인 미만 사업장은 법적 의무 없음. 계약된 조건이 있으면 여기서 직원별로 지정하세요.<br>
          <strong>배수</strong>: 시급의 N배 지급 (1.2 입력 시 기본급 외 0.2배 추가) &nbsp;|&nbsp;
          <strong>고정금액</strong>: 시간당 N원 추가
        </p>
        <?php
        $premiumRows = [
            ['night',    '야간수당', '22:00 ~ 06:00'],
            ['overtime', '연장수당', '1일 8h / 주 40h 초과'],
            ['holiday',  '휴일수당', '휴일 근무'],
        ];
        foreach ($premiumRows as [$kind, $label, $sub]):
            $curType  = h($employee["{$kind}_premium_type"]  ?? 'global');
            $curValue = h($employee["{$kind}_premium_value"] ?? '');
        ?>
        <div class="mb-3 p-3 rounded" style="background:var(--c-cream)">
          <div class="d-flex align-items-center mb-2">
            <span class="fw-semibold me-2"><?= $label ?></span>
            <span class="text-muted small"><?= $sub ?></span>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <select name="<?= $kind ?>_premium_type" class="form-select form-select-sm"
                    style="max-width:200px"
                    onchange="updatePremium('<?= $kind ?>')">
              <option value="global"     <?= $curType === 'global'     ? 'selected' : '' ?>>전역 설정 따름</option>
              <option value="none"       <?= $curType === 'none'       ? 'selected' : '' ?>>미적용</option>
              <option value="multiplier" <?= $curType === 'multiplier' ? 'selected' : '' ?>>배수</option>
              <option value="fixed"      <?= $curType === 'fixed'      ? 'selected' : '' ?>>고정금액</option>
            </select>
            <div id="wrap_<?= $kind ?>" class="input-group input-group-sm" style="max-width:180px;display:none">
              <input type="number" name="<?= $kind ?>_premium_value"
                     id="val_<?= $kind ?>" class="form-control"
                     value="<?= $curValue ?>" min="0" step="0.05">
              <span class="input-group-text" id="unit_<?= $kind ?>">배</span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-save me-1"></i>저장
        </button>
        <a href="<?= url('employees') ?>" class="btn btn-outline-secondary">취소</a>
      </div>

<script>
function updatePremium(kind) {
  var sel  = document.querySelector('[name="' + kind + '_premium_type"]');
  var wrap = document.getElementById('wrap_' + kind);
  var val  = document.getElementById('val_'  + kind);
  var unit = document.getElementById('unit_' + kind);
  var type = sel.value;
  if (type === 'multiplier') {
    wrap.style.display = '';
    val.step = '0.05'; val.min = '1';
    val.placeholder = '예) 1.2';
    unit.textContent = '배';
  } else if (type === 'fixed') {
    wrap.style.display = '';
    val.step = '100'; val.min = '0';
    val.placeholder = '예) 500';
    unit.textContent = '원/시';
  } else {
    wrap.style.display = 'none';
  }
}
['night','overtime','holiday'].forEach(updatePremium);
</script>
    </form>
  </div>
</div>

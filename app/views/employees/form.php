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

<form method="post"
      action="<?= $isEdit ? url('employees', 'edit', ['id' => $employee['id']]) : url('employees', 'create') ?>">
<?= csrf_field() ?>

<div class="row g-4">

  <!-- ── 왼쪽: 기본 정보 ─────────────────────────────────── -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-person me-1"></i>기본 정보
      </div>
      <div class="card-body">

        <div class="mb-3">
          <label class="form-label fw-semibold">직원명 <span class="text-danger">*</span></label>
          <input type="text" name="name"
                 class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                 value="<?= h($employee['name'] ?? '') ?>" required>
          <?php if (isset($errors['name'])): ?>
            <div class="invalid-feedback"><?= h($errors['name']) ?></div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">
            시급 <span class="text-danger">*</span>
            <i class="bi bi-question-circle text-muted ms-1" data-bs-toggle="tooltip"
               title="시간당 임금. 2026년 최저시급 10,320원 이상이어야 합니다."></i>
          </label>
          <div class="input-group">
            <input type="number" name="hourly_wage"
                   class="form-control <?= isset($errors['hourly_wage']) ? 'is-invalid' : '' ?>"
                   value="<?= h($employee['hourly_wage'] ?? MinimumWage::currentHourlyWage()) ?>"
                   min="1" required>
            <span class="input-group-text">원/시간</span>
            <?php if (isset($errors['hourly_wage'])): ?>
              <div class="invalid-feedback"><?= h($errors['hourly_wage']) ?></div>
            <?php endif; ?>
          </div>
          <?php $curMinWage = MinimumWage::currentHourlyWage(); ?>
          <div class="form-text">
            <?= date('Y') ?>년 법정 최저시급: <?= number_format($curMinWage) ?>원
          </div>
          <div class="form-text text-warning-emphasis small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>수습 감액(최저시급 90%)은 ① 계약기간 1년 이상 + ② 수습 3개월 이내일 때만 적용 가능합니다.</strong>
            편의점·카페 스태프 등 단순노무직은 계약기간과 무관하게 감액 불가(최저시급 100% 지급).
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">
              주 소정근로시간
              <i class="bi bi-question-circle text-muted ms-1" data-bs-toggle="tooltip"
                 title="계약서상 주당 근무 시간. 15시간 이상이면 주휴수당 대상."></i>
            </label>
            <div class="input-group">
              <input type="number" name="weekly_scheduled_hours" class="form-control"
                     value="<?= h($employee['weekly_scheduled_hours'] ?? 40) ?>"
                     min="0" max="80" step="0.5">
              <span class="input-group-text">시간</span>
            </div>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">
              주 소정근로일
              <i class="bi bi-question-circle text-muted ms-1" data-bs-toggle="tooltip"
                 title="계약서상 주당 근무 일수. 개근 여부 판단에 사용."></i>
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
              <i class="bi bi-question-circle text-muted ms-1" data-bs-toggle="tooltip"
                 title="주 15시간 이상, 소정근로일 개근 시 발생. 단기 아르바이트 등 제외 시 끄세요."></i>
            </label>
          </div>
        </div>

        <hr>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">입사일 <span class="text-danger">*</span></label>
            <input type="date" name="employment_start_date"
                   class="form-control <?= isset($errors['employment_start_date']) ? 'is-invalid' : '' ?>"
                   value="<?= h($employee['employment_start_date'] ?? date('Y-m-d')) ?>" required>
            <?php if (isset($errors['employment_start_date'])): ?>
              <div class="invalid-feedback"><?= h($errors['employment_start_date']) ?></div>
            <?php endif; ?>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">계약 종료일 <span class="text-muted small fw-normal">(선택)</span></label>
            <input type="date" name="employment_end_date" class="form-control"
                   value="<?= h($employee['employment_end_date'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">메모</label>
          <textarea name="memo" class="form-control" rows="2"><?= h($employee['memo'] ?? '') ?></textarea>
          <div class="form-text text-danger-emphasis">
            <i class="bi bi-shield-exclamation me-1"></i>주민등록번호, 계좌번호, 건강정보 등 민감한 개인정보는 입력하지 마세요.
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ── 오른쪽: 가산수당 설정 ──────────────────────────── -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-percent me-1"></i>가산수당 개별 설정
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          5인 미만 사업장은 법적 의무 없음. 계약된 조건이 있으면 직원별로 지정하세요.
        </p>

        <?php
        $premiumRows = [
            ['night',    '야간수당', '22:00 ~ 06:00'],
            ['overtime', '연장수당', '1일 8시간 / 주 40시간 초과'],
            ['holiday',  '휴일수당', '휴일 근무'],
        ];
        foreach ($premiumRows as [$kind, $label, $sub]):
            $curType  = h($employee["{$kind}_premium_type"]  ?? 'global');
            $curValue = h($employee["{$kind}_premium_value"] ?? '');
        ?>
        <div class="mb-3 p-3 rounded" style="background:var(--c-cream)">
          <div class="d-flex align-items-baseline gap-2 mb-2">
            <span class="fw-semibold"><?= $label ?></span>
            <span class="text-muted small"><?= $sub ?></span>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <select name="<?= $kind ?>_premium_type"
                    class="form-select form-select-sm"
                    style="max-width:180px"
                    onchange="updatePremium('<?= $kind ?>')">
              <option value="global"     <?= $curType === 'global'     ? 'selected' : '' ?>>전역 설정 따름</option>
              <option value="none"       <?= $curType === 'none'       ? 'selected' : '' ?>>미적용</option>
              <option value="multiplier" <?= $curType === 'multiplier' ? 'selected' : '' ?>>배수</option>
              <option value="fixed"      <?= $curType === 'fixed'      ? 'selected' : '' ?>>고정금액</option>
            </select>
            <div id="wrap_<?= $kind ?>" class="input-group input-group-sm" style="max-width:160px;display:none">
              <input type="number" name="<?= $kind ?>_premium_value"
                     id="val_<?= $kind ?>" class="form-control"
                     value="<?= $curValue ?>" min="0" step="0.05">
              <span class="input-group-text" id="unit_<?= $kind ?>">배</span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- 참고 안내 -->
        <div class="p-3 rounded small text-muted mt-2" style="background:#f8f9fa">
          <div class="mb-1"><strong>배수</strong> 예시: 1.2 입력 → 시급의 1.2배 지급 (0.2배 가산)</div>
          <div><strong>고정금액</strong> 예시: 500 입력 → 해당 시간당 500원 추가</div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /row -->

<div class="d-flex flex-wrap gap-2 mt-4 align-items-center">
  <button type="submit" name="create_contract" value="" class="btn btn-primary px-4">
    <i class="bi bi-save me-1"></i>저장
  </button>
  <?php if (!$isEdit): ?>
  <button type="submit" name="create_contract" value="1"
          class="btn px-4 text-white" style="background:var(--c-teal)">
    <i class="bi bi-file-earmark-text me-1"></i>저장 후 계약서 작성
  </button>
  <?php endif; ?>
  <a href="<?= url('employees') ?>" class="btn btn-outline-secondary">취소</a>
  <?php if ($isEdit && !empty($memberId)): ?>
  <a href="<?= url('members', 'contract', ['id' => $memberId]) ?>"
     target="_blank" class="btn btn-outline-dark ms-auto">
    <i class="bi bi-file-earmark-text me-1"></i>근로계약서 생성
  </a>
  <?php endif; ?>
</div>

</form>

<script>
function updatePremium(kind) {
  var sel  = document.querySelector('[name="' + kind + '_premium_type"]');
  var wrap = document.getElementById('wrap_' + kind);
  var val  = document.getElementById('val_'  + kind);
  var unit = document.getElementById('unit_' + kind);
  if (sel.value === 'multiplier') {
    wrap.style.display = '';
    val.step = '0.05'; val.min = '1'; val.placeholder = '예) 1.2';
    unit.textContent = '배';
  } else if (sel.value === 'fixed') {
    wrap.style.display = '';
    val.step = '100'; val.min = '0'; val.placeholder = '예) 500';
    unit.textContent = '원/시';
  } else {
    wrap.style.display = 'none';
  }
}
['night','overtime','holiday'].forEach(updatePremium);
</script>

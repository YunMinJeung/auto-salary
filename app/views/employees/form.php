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

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-save me-1"></i>저장
        </button>
        <a href="<?= url('employees') ?>" class="btn btn-outline-secondary">취소</a>
      </div>
    </form>
  </div>
</div>

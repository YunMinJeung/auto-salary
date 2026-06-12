<?php
$s = $standard ?? [];
$pct = fn($v, $d) => htmlspecialchars((string)($v ?? $d), ENT_QUOTES, 'UTF-8');
?>
<a href="<?= url('admin', 'standards') ?>" class="text-decoration-none small d-inline-block mb-3"><i class="bi bi-arrow-left"></i> 계산 기준 목록</a>

<div class="admin-card" style="max-width:720px">
  <div class="admin-card-header"><span><i class="bi bi-sliders me-1"></i><?= h($title) ?></span></div>
  <div class="admin-card-body">
    <form method="POST" action="<?= url('admin', 'standard_form', !empty($s['id']) ? ['id' => $s['id']] : []) ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label small">연도 <span class="text-danger">*</span></label>
          <input type="number" name="year" class="form-control form-control-sm" required value="<?= h((string)($s['year'] ?? date('Y'))) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">최저시급(원) <span class="text-danger">*</span></label>
          <input type="number" name="min_hourly_wage" class="form-control form-control-sm" required value="<?= h((string)($s['min_hourly_wage'] ?? '')) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?= !isset($s['is_active']) || (int)$s['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label small" for="is_active">활성</label>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label small">심야 시작</label>
          <input type="time" name="night_start_time" class="form-control form-control-sm" value="<?= h(substr((string)($s['night_start_time'] ?? '22:00:00'), 0, 5)) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small">심야 종료</label>
          <input type="time" name="night_end_time" class="form-control form-control-sm" value="<?= h(substr((string)($s['night_end_time'] ?? '06:00:00'), 0, 5)) ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label small">국민연금 요율</label>
          <input type="number" step="0.0001" name="insurance_national_pension_rate" class="form-control form-control-sm" value="<?= $pct($s['insurance_national_pension_rate'] ?? null, '0.0450') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">건강보험 요율</label>
          <input type="number" step="0.0001" name="insurance_health_rate" class="form-control form-control-sm" value="<?= $pct($s['insurance_health_rate'] ?? null, '0.0354') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">장기요양 요율</label>
          <input type="number" step="0.0001" name="insurance_long_term_care_rate" class="form-control form-control-sm" value="<?= $pct($s['insurance_long_term_care_rate'] ?? null, '0.1281') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">고용보험 요율</label>
          <input type="number" step="0.0001" name="insurance_employment_rate" class="form-control form-control-sm" value="<?= $pct($s['insurance_employment_rate'] ?? null, '0.0090') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label small">적용 시작일 <span class="text-danger">*</span></label>
          <input type="date" name="applies_from" class="form-control form-control-sm" required value="<?= h((string)($s['applies_from'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small">적용 종료일</label>
          <input type="date" name="applies_to" class="form-control form-control-sm" value="<?= h((string)($s['applies_to'] ?? '')) ?>">
        </div>

        <div class="col-12">
          <label class="form-label small">설명</label>
          <textarea name="description" class="form-control form-control-sm" rows="2"><?= h((string)($s['description'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-accent);color:#fff"><i class="bi bi-check-lg me-1"></i>저장</button>
        <a href="<?= url('admin', 'standards') ?>" class="btn btn-sm btn-outline-secondary">취소</a>
      </div>
    </form>
  </div>
</div>

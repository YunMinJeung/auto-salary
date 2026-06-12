<?php
$isEdit = ($action === 'edit');
$sd = $schedule;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-calendar-week me-2 text-primary"></i><?= h($title) ?>
  </h1>
  <a href="<?= url('schedules') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>근무표로
  </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
  <ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:560px">
  <div class="card-body">
    <form method="post"
          action="<?= $isEdit ? url('schedules', 'edit', ['id' => $sd['id']]) : url('schedules', 'create') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">직원 <span class="text-danger">*</span></label>
        <select name="employee_id" class="form-select" required>
          <option value="">— 선택 —</option>
          <?php foreach ($employees as $emp): ?>
          <option value="<?= $emp['id'] ?>" <?= (int)($sd['employee_id'] ?? 0) === (int)$emp['id'] ? 'selected' : '' ?>>
            <?= h($emp['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">날짜 <span class="text-danger">*</span></label>
        <input type="date" name="schedule_date" class="form-control"
               value="<?= h($sd['schedule_date'] ?? date('Y-m-d')) ?>" required>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">시작시간 <span class="text-danger">*</span></label>
          <input type="time" name="start_time" class="form-control"
                 value="<?= h(substr((string)($sd['start_time'] ?? ''), 0, 5)) ?>" required>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">종료시간 <span class="text-danger">*</span></label>
          <input type="time" name="end_time" class="form-control"
                 value="<?= h(substr((string)($sd['end_time'] ?? ''), 0, 5)) ?>" required>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">휴게 (분)</label>
        <input type="number" name="break_minutes" class="form-control" min="0" step="5"
               value="<?= (int)($sd['break_minutes'] ?? 0) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">메모</label>
        <input type="text" name="memo" class="form-control" maxlength="255"
               value="<?= h($sd['memo'] ?? '') ?>">
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= url('schedules') ?>" class="btn btn-outline-secondary">취소</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i>저장
        </button>
      </div>
    </form>
  </div>
</div>
